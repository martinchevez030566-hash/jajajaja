<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/librerias/XMLGenerator.class.php
 * Descripción: Generador de XML UBL 2.1 para Guías de Remisión según SUNAT
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Dependencias:
 * - DOMDocument (PHP)
 * - Database.class.php
 * 
 * Estándar: UBL 2.1 DespatchAdvice
 * 
 * =======================================================
 */

class XMLGenerator {
    
    private $db;
    private $config;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once __DIR__ . '/Database.class.php';
        $this->db = Database::getInstance();
        $this->config = $this->db->getConfig();
    }
    
    /**
     * Generar XML completo de Guía de Remisión
     * @param int $idGuia ID de la guía
     * @return string|false XML generado o false en error
     */
    public function generarXML($idGuia) {
        try {
            // Obtener datos de la guía
            $guia = $this->obtenerDatosGuia($idGuia);
            if (!$guia) {
                throw new Exception("No se encontró la guía con ID: $idGuia");
            }
            
            // Crear documento XML
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;
            $xml->preserveWhiteSpace = false;
            
            // Crear elemento raíz DespatchAdvice
            $despatchAdvice = $xml->createElementNS(
                'urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2',
                'DespatchAdvice'
            );
            
            // Agregar namespaces
            $this->agregarNamespaces($despatchAdvice, $xml);
            
            // Agregar extensiones UBL
            $this->agregarExtensiones($despatchAdvice, $xml);
            
            // UBL Version
            $this->agregarElemento($despatchAdvice, $xml, 'cbc:UBLVersionID', '2.1');
            $this->agregarElemento($despatchAdvice, $xml, 'cbc:CustomizationID', '2.0');
            
            // ID del documento (Serie-Número)
            $nroGuia = $guia['SERIE'] . '-' . str_pad($guia['NUMERO'], 8, '0', STR_PAD_LEFT);
            $this->agregarElemento($despatchAdvice, $xml, 'cbc:ID', $nroGuia);
            
            // Fecha y hora de emisión
            $fechaEmision = date('Y-m-d', strtotime($guia['FECHA_EMISION']));
            $horaEmision = date('H:i:s', strtotime($guia['HORA_EMISION']));
            $this->agregarElemento($despatchAdvice, $xml, 'cbc:IssueDate', $fechaEmision);
            $this->agregarElemento($despatchAdvice, $xml, 'cbc:IssueTime', $horaEmision);
            
            // Tipo de documento (09 = Guía de Remisión Remitente)
            $this->agregarElemento($despatchAdvice, $xml, 'cbc:DespatchAdviceTypeCode', '09');
            
            // Firma digital (placeholder)
            $this->agregarFirma($despatchAdvice, $xml);
            
            // Remitente (emisor)
            $this->agregarRemitente($despatchAdvice, $xml, $guia);
            
            // Destinatario
            $this->agregarDestinatario($despatchAdvice, $xml, $guia);
            
            // Envío (datos del traslado)
            $this->agregarEnvio($despatchAdvice, $xml, $guia);
            
            // Líneas de detalle (productos)
            $this->agregarLineasDetalle($despatchAdvice, $xml, $guia);
            
            // Agregar elemento raíz al documento
            $xml->appendChild($despatchAdvice);
            
            return $xml->saveXML();
            
        } catch (Exception $e) {
            error_log("Error generando XML: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener datos completos de la guía
     * @param int $idGuia
     * @return array|false
     */
    private function obtenerDatosGuia($idGuia) {
        $sql = "SELECT 
                    G.*,
                    A.A1_CDESCRI AS ALMACEN_NOMBRE,
                    A.A1_CDIRECC AS ALMACEN_DIRECCION,
                    A.A1_CCODUBI AS ALMACEN_UBIGEO,
                    U1.departamento_inei AS UBIGEO_PARTIDA_DPTO,
                    U1.provincia_inei AS UBIGEO_PARTIDA_PROV,
                    U1.distrito AS UBIGEO_PARTIDA_DIST,
                    U2.departamento_inei AS UBIGEO_LLEGADA_DPTO,
                    U2.provincia_inei AS UBIGEO_LLEGADA_PROV,
                    U2.distrito AS UBIGEO_LLEGADA_DIST,
                    T.NOMBRE AS TRANSPORTISTA_NOMBRE,
                    T.DOCUMENTO AS TRANSPORTISTA_DOC,
                    T.PLACA,
                    T.LICENCIA
                FROM MPCL.dbo.TBL_GUIAS_CAB G
                LEFT JOIN RSFACCAR.dbo.AL0005ALMA A ON G.CODIGO_ALMACEN = A.A1_CALMA
                LEFT JOIN RSFACCAR.dbo.TB_UBIGEOS U1 ON A.A1_CCODUBI = U1.ubigeo_inei
                LEFT JOIN RSFACCAR.dbo.TB_UBIGEOS U2 ON G.UBIGEO_LLEGADA = U2.ubigeo_inei
                LEFT JOIN MPCL.dbo.TBL_TRANSPORTISTA T ON G.ID_TRANSPORTISTA = T.ID
                WHERE G.ID = ?";
        
        $guia = $this->db->selectOne($sql, [$idGuia]);
        
        if ($guia) {
            // Obtener detalle
            $sqlDet = "SELECT * FROM MPCL.dbo.TBL_GUIAS_DET WHERE ID_GUIA_CAB = ? ORDER BY ITEM";
            $guia['DETALLE'] = $this->db->select($sqlDet, [$idGuia]);
        }
        
        return $guia;
    }
    
    /**
     * Agregar namespaces al elemento raíz
     */
    private function agregarNamespaces($elemento, $xml) {
        $elemento->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:cac',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2'
        );
        $elemento->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:cbc',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
        );
        $elemento->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:ds',
            'http://www.w3.org/2000/09/xmldsig#'
        );
        $elemento->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:ext',
            'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2'
        );
    }
    
    /**
     * Agregar extensiones UBL
     */
    private function agregarExtensiones($padre, $xml) {
        $extensions = $xml->createElement('ext:UBLExtensions');
        $extension = $xml->createElement('ext:UBLExtension');
        $extensionContent = $xml->createElement('ext:ExtensionContent');
        
        // Aquí irá la firma digital después
        
        $extension->appendChild($extensionContent);
        $extensions->appendChild($extension);
        $padre->appendChild($extensions);
    }
    
    /**
     * Agregar firma digital (placeholder)
     */
    private function agregarFirma($padre, $xml) {
        $signature = $xml->createElement('cac:Signature');
        
        $this->agregarElemento($signature, $xml, 'cbc:ID', $this->config['RUC_EMPRESA']);
        
        $signatoryParty = $xml->createElement('cac:SignatoryParty');
        $partyIdentification = $xml->createElement('cac:PartyIdentification');
        $this->agregarElemento($partyIdentification, $xml, 'cbc:ID', $this->config['RUC_EMPRESA']);
        $signatoryParty->appendChild($partyIdentification);
        
        $partyName = $xml->createElement('cac:PartyName');
        $this->agregarElemento($partyName, $xml, 'cbc:Name', $this->config['RAZON_SOCIAL']);
        $signatoryParty->appendChild($partyName);
        
        $signature->appendChild($signatoryParty);
        
        $digitalSignature = $xml->createElement('cac:DigitalSignatureAttachment');
        $externalReference = $xml->createElement('cac:ExternalReference');
        $this->agregarElemento($externalReference, $xml, 'cbc:URI', '#signatureGRE');
        $digitalSignature->appendChild($externalReference);
        $signature->appendChild($digitalSignature);
        
        $padre->appendChild($signature);
    }
    
    /**
     * Agregar datos del remitente (emisor)
     */
    private function agregarRemitente($padre, $xml, $guia) {
        $despatchSupplier = $xml->createElement('cac:DespatchSupplierParty');
        $party = $xml->createElement('cac:Party');
        
        // Identificación RUC
        $partyIdentification = $xml->createElement('cac:PartyIdentification');
        $id = $xml->createElement('cbc:ID', $this->config['RUC_EMPRESA']);
        $id->setAttribute('schemeID', '6'); // 6 = RUC
        $partyIdentification->appendChild($id);
        $party->appendChild($partyIdentification);
        
        // Nombre comercial
        $partyName = $xml->createElement('cac:PartyName');
        $this->agregarElemento($partyName, $xml, 'cbc:Name', $this->config['NOMBRE_COMERCIAL']);
        $party->appendChild($partyName);
        
        // Razón social
        $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
        $this->agregarElemento($partyLegalEntity, $xml, 'cbc:RegistrationName', $this->config['RAZON_SOCIAL']);
        $party->appendChild($partyLegalEntity);
        
        $despatchSupplier->appendChild($party);
        $padre->appendChild($despatchSupplier);
    }
    
    /**
     * Agregar datos del destinatario
     */
    private function agregarDestinatario($padre, $xml, $guia) {
        $deliveryCustomer = $xml->createElement('cac:DeliveryCustomerParty');
        $party = $xml->createElement('cac:Party');
        
        // Identificación del cliente
        $partyIdentification = $xml->createElement('cac:PartyIdentification');
        $id = $xml->createElement('cbc:ID', trim($guia['NUM_DOC_DESTINATARIO']));
        $id->setAttribute('schemeID', $guia['TIPO_DOC_DESTINATARIO']); // 1=DNI, 6=RUC
        $partyIdentification->appendChild($id);
        $party->appendChild($partyIdentification);
        
        // Nombre/Razón social
        $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
        $this->agregarElemento($partyLegalEntity, $xml, 'cbc:RegistrationName', $guia['RAZON_SOCIAL_DESTINATARIO']);
        $party->appendChild($partyLegalEntity);
        
        $deliveryCustomer->appendChild($party);
        $padre->appendChild($deliveryCustomer);
    }
    
    /**
     * Agregar datos del envío (traslado)
     */
    private function agregarEnvio($padre, $xml, $guia) {
        $shipment = $xml->createElement('cac:Shipment');
        
        // ID del envío
        $this->agregarElemento($shipment, $xml, 'cbc:ID', '1');
        
        // Código de motivo de traslado
        $this->agregarElemento($shipment, $xml, 'cbc:HandlingCode', $guia['MOTIVO_TRASLADO']);
        
        // Información adicional del motivo
        if ($guia['DESCRIPCION_MOTIVO']) {
            $this->agregarElemento($shipment, $xml, 'cbc:Information', $guia['DESCRIPCION_MOTIVO']);
        }
        
        // Peso bruto
        $peso = $xml->createElement('cbc:GrossWeightMeasure', number_format($guia['PESO_TOTAL'], 2, '.', ''));
        $peso->setAttribute('unitCode', $guia['UNIDAD_PESO']); // KGM
        $shipment->appendChild($peso);
        
        // Número de bultos
        if ($guia['NUM_BULTOS']) {
            $this->agregarElemento($shipment, $xml, 'cbc:TotalTransportHandlingUnitQuantity', $guia['NUM_BULTOS']);
        }
        
        // Documento relacionado (factura/boleta)
        if ($guia['TIPO_DOC_REL'] && $guia['SERIE_DOC_REL'] && $guia['NUMERO_DOC_REL']) {
            $additionalDoc = $xml->createElement('cac:AdditionalDocumentReference');
            $nroDocRel = $guia['SERIE_DOC_REL'] . '-' . $guia['NUMERO_DOC_REL'];
            $this->agregarElemento($additionalDoc, $xml, 'cbc:ID', $nroDocRel);
            $this->agregarElemento($additionalDoc, $xml, 'cbc:DocumentTypeCode', $guia['TIPO_DOC_REL']);
            $shipment->appendChild($additionalDoc);
        }
        
        // Dirección de partida
        $this->agregarDireccionPartida($shipment, $xml, $guia);
        
        // Dirección de llegada
        $this->agregarDireccionLlegada($shipment, $xml, $guia);
        
        // Datos del transporte
        $this->agregarDatosTransporte($shipment, $xml, $guia);
        
        $padre->appendChild($shipment);
    }
    
    /**
     * Agregar dirección de partida
     */
    private function agregarDireccionPartida($padre, $xml, $guia) {
        $originAddress = $xml->createElement('cac:OriginAddress');
        
        $ubigeoPartida = trim($guia['ALMACEN_UBIGEO']);
        $this->agregarElemento($originAddress, $xml, 'cbc:ID', $ubigeoPartida);
        $this->agregarElemento($originAddress, $xml, 'cbc:StreetName', $guia['ALMACEN_DIRECCION']);
        
        // Subdivisiones (opcional)
        if ($guia['UBIGEO_PARTIDA_DIST']) {
            $this->agregarElemento($originAddress, $xml, 'cbc:CitySubdivisionName', $guia['UBIGEO_PARTIDA_DIST']);
        }
        if ($guia['UBIGEO_PARTIDA_PROV']) {
            $this->agregarElemento($originAddress, $xml, 'cbc:CityName', $guia['UBIGEO_PARTIDA_PROV']);
        }
        if ($guia['UBIGEO_PARTIDA_DPTO']) {
            $this->agregarElemento($originAddress, $xml, 'cbc:CountrySubentity', $guia['UBIGEO_PARTIDA_DPTO']);
        }
        
        // País
        $country = $xml->createElement('cac:Country');
        $this->agregarElemento($country, $xml, 'cbc:IdentificationCode', 'PE');
        $originAddress->appendChild($country);
        
        $padre->appendChild($originAddress);
    }
    
    /**
     * Agregar dirección de llegada
     */
    private function agregarDireccionLlegada($padre, $xml, $guia) {
        $delivery = $xml->createElement('cac:Delivery');
        $deliveryAddress = $xml->createElement('cac:DeliveryAddress');
        
        $ubigeoLlegada = trim($guia['UBIGEO_LLEGADA']);
        $this->agregarElemento($deliveryAddress, $xml, 'cbc:ID', $ubigeoLlegada);
        $this->agregarElemento($deliveryAddress, $xml, 'cbc:StreetName', $guia['DIRECCION_LLEGADA']);
        
        // Subdivisiones
        if ($guia['UBIGEO_LLEGADA_DIST']) {
            $this->agregarElemento($deliveryAddress, $xml, 'cbc:CitySubdivisionName', $guia['UBIGEO_LLEGADA_DIST']);
        }
        if ($guia['UBIGEO_LLEGADA_PROV']) {
            $this->agregarElemento($deliveryAddress, $xml, 'cbc:CityName', $guia['UBIGEO_LLEGADA_PROV']);
        }
        if ($guia['UBIGEO_LLEGADA_DPTO']) {
            $this->agregarElemento($deliveryAddress, $xml, 'cbc:CountrySubentity', $guia['UBIGEO_LLEGADA_DPTO']);
        }
        
        // País
        $country = $xml->createElement('cac:Country');
        $this->agregarElemento($country, $xml, 'cbc:IdentificationCode', 'PE');
        $deliveryAddress->appendChild($country);
        
        $delivery->appendChild($deliveryAddress);
        $padre->appendChild($delivery);
    }
    
    /**
     * Agregar datos del transporte
     */
    private function agregarDatosTransporte($padre, $xml, $guia) {
        $shipmentStage = $xml->createElement('cac:ShipmentStage');
        
        // Modo de transporte (01=Transporte público, 02=Transporte privado)
        $this->agregarElemento($shipmentStage, $xml, 'cbc:TransportModeCode', '01');
        
        // Fecha de inicio de traslado
        $transitPeriod = $xml->createElement('cac:TransitPeriod');
        $fechaTraslado = date('Y-m-d', strtotime($guia['FECHA_INICIO_TRASLADO']));
        $this->agregarElemento($transitPeriod, $xml, 'cbc:StartDate', $fechaTraslado);
        $shipmentStage->appendChild($transitPeriod);
        
        // Transportista
        $carrierParty = $xml->createElement('cac:CarrierParty');
        
        $partyIdentification = $xml->createElement('cac:PartyIdentification');
        $tipoDocTransp = (strlen(trim($guia['TRANSPORTISTA_DOC'])) == 11) ? '6' : '1';
        $id = $xml->createElement('cbc:ID', trim($guia['TRANSPORTISTA_DOC']));
        $id->setAttribute('schemeID', $tipoDocTransp);
        $partyIdentification->appendChild($id);
        $carrierParty->appendChild($partyIdentification);
        
        $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
        $this->agregarElemento($partyLegalEntity, $xml, 'cbc:RegistrationName', $guia['TRANSPORTISTA_NOMBRE']);
        $carrierParty->appendChild($partyLegalEntity);
        
        $shipmentStage->appendChild($carrierParty);
        
        // Conductor (si aplica)
        if ($guia['LICENCIA']) {
            $driverPerson = $xml->createElement('cac:DriverPerson');
            
            // Licencia de conducir
            $identityDoc = $xml->createElement('cac:IdentityDocumentReference');
            $this->agregarElemento($identityDoc, $xml, 'cbc:ID', $guia['LICENCIA']);
            $driverPerson->appendChild($identityDoc);
            
            $shipmentStage->appendChild($driverPerson);
        }
        
        $padre->appendChild($shipmentStage);
        
        // Vehículo (placa)
        if ($guia['PLACA']) {
            $transportHandlingUnit = $xml->createElement('cac:TransportHandlingUnit');        
			$transportEquipment = $xml->createElement('cac:TransportEquipment');
        $this->agregarElemento($transportEquipment, $xml, 'cbc:ID', trim($guia['PLACA']));
        $transportHandlingUnit->appendChild($transportEquipment);        
		$padre->appendChild($transportHandlingUnit);
    }
}/**
 * Agregar líneas de detalle (productos)
 */
private function agregarLineasDetalle($padre, $xml, $guia) {
    foreach ($guia['DETALLE'] as $detalle) {
        $despatchLine = $xml->createElement('cac:DespatchLine');        // ID de la línea
        $this->agregarElemento($despatchLine, $xml, 'cbc:ID', $detalle['ITEM']);        // Cantidad entregada
        $cantidad = $xml->createElement('cbc:DeliveredQuantity', 
            number_format($detalle['CANTIDAD'], 2, '.', '')
        );
        $cantidad->setAttribute('unitCode', $detalle['UNIDAD_MEDIDA']);
        $despatchLine->appendChild($cantidad);        // Referencia a línea de orden (opcional)
        $orderLineReference = $xml->createElement('cac:OrderLineReference');
        $this->agregarElemento($orderLineReference, $xml, 'cbc:LineID', $detalle['ITEM']);
        $despatchLine->appendChild($orderLineReference);        // Información del producto
        $item = $xml->createElement('cac:Item');        // Descripción
        $this->agregarElemento($item, $xml, 'cbc:Description', $detalle['DESCRIPCION']);        // Identificación del producto
        $sellersItemId = $xml->createElement('cac:SellersItemIdentification');
        $this->agregarElemento($sellersItemId, $xml, 'cbc:ID', $detalle['CODIGO_PRODUCTO']);
        $item->appendChild($sellersItemId);        $despatchLine->appendChild($item);        $padre->appendChild($despatchLine);
    }
}/**
 * Método auxiliar para agregar elemento con texto
 */
private function agregarElemento($padre, $xml, $nombre, $valor) {
    $elemento = $xml->createElement($nombre, htmlspecialchars($valor, ENT_XML1, 'UTF-8'));
    $padre->appendChild($elemento);
    return $elemento;
}
/**
 * Validar XML contra esquema XSD (opcional)
 * @param string $xml XML a validar
 * @return bool
 */
public function validarXML($xml) {
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    
    // Ruta al esquema XSD de SUNAT (si lo tienes)
    $xsdPath = __DIR__ . '/../schemas/UBL-DespatchAdvice-2.1.xsd';
    
    if (file_exists($xsdPath)) {
        return $doc->schemaValidate($xsdPath);
    }
    
    return true; // Si no hay esquema, asumimos válido
}
}
?>