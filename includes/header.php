<?php
/**
 * =======================================================
 * SISTEMA DE GUÍAS DE REMISIÓN ELECTRÓNICA - GRE
 * =======================================================
 * 
 * Archivo: gre_mpcl/includes/header.php
 * Descripción: Encabezado común para todas las páginas del sistema
 * Autor: Sistema GRE
 * Fecha: 2025-10-26
 * 
 * Variables requeridas antes de incluir:
 * - $pageTitle: Título de la página
 * - $currentPage: Identificador de página actual (para menú activo)
 * 
 * =======================================================
 */

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

// Valores por defecto
$pageTitle = isset($pageTitle) ? $pageTitle : 'Sistema GRE';
$currentPage = isset($currentPage) ? $currentPage : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($pageTitle) ?> - Sistema GRE</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- jQuery UI (para Autocomplete) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    
    <!-- Select2 (para selects avanzados) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Estilos personalizados GRE -->
    <link href="assets/css/gre-styles.css" rel="stylesheet">
    
    <!-- Estilos adicionales por página -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    
    <!-- NAVBAR PRINCIPAL -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gre-primary shadow-sm">
        <div class="container-fluid">
            <!-- Logo y nombre -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-truck-moving fa-2x me-2"></i>
                <div>
                    <div class="fw-bold">Sistema GRE</div>
                    <small class="d-block" style="font-size: 0.7rem;">Guías de Remisión Electrónica</small>
                </div>
            </a>
            
            <!-- Botón toggle móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menú principal -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage == 'dashboard' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-home"></i> Panel Principal
                        </a>
                    </li>
                    
                    <!-- Emitir Guía -->
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage == 'emitir' ? 'active' : '' ?>" href="emitir_guia.php">
                            <i class="fas fa-plus-circle"></i> Nueva Guía
                        </a>
                    </li>
                    
                    <!-- Gestión (Dropdown) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['transportistas', 'almacenes', 'configuracion']) ? 'active' : '' ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Gestión
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?= $currentPage == 'transportistas' ? 'active' : '' ?>" 
                                   href="modulos/transportistas.php">
                                    <i class="fas fa-truck"></i> Transportistas
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= $currentPage == 'almacenes' ? 'active' : '' ?>" 
                                   href="modulos/almacenes.php">
                                    <i class="fas fa-warehouse"></i> Almacenes y Series
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item <?= $currentPage == 'configuracion' ? 'active' : '' ?>" 
                                   href="modulos/configuracion.php">
                                    <i class="fas fa-sliders-h"></i> Configuración
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Reportes -->
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage == 'reportes' ? 'active' : '' ?>" href="modulos/reportes.php">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                    </li>
                    
                </ul>
                
                <!-- Usuario y opciones -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> 
                            <span class="d-none d-lg-inline"><?= htmlspecialchars($_SESSION['usuario']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user"></i> Mi Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="probarConexionSunat()">
                                    <i class="fas fa-plug"></i> Probar Conexión SUNAT
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- BREADCRUMB (opcional) -->
    <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
    <div class="container-fluid mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <?php foreach ($breadcrumb as $index => $crumb): ?>
                    <?php if ($index == count($breadcrumb) - 1): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['text']) ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['text']) ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
    <?php endif; ?>
    
    <!-- CONTENIDO PRINCIPAL -->
    <div class="container-fluid py-4" id="mainContent">