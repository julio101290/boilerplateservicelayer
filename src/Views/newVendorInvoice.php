<?= $this->include('julio101290\boilerplate\Views\load/toggle') ?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->include('julio101290\boilerplateproducts\Views\load\zoom') ?>
<!-- Extend from layout index -->
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<!-- Section content -->
<?= $this->section('content') ?>

<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/dataHeadVendorInvoice') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/productosModalVendorInvoice') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/modalPayment') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/modalPaymentList') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesVendorInvoice/moreInfoRow') ?>
<?= $this->include('julio101290\boilerplateproducts\Views\modulesProducts/modalCaptureProducts') ?>
<?= $this->include('julio101290\boilerplatecustumers\Views\modulesCustumers/modalCaptureCustumers') ?>

<?= $this->include('julio101290\boilerplatedrivers\Views\modulesChoferes/modalCaptureChoferes') ?>
<?= $this->include('julio101290\boilerplatevehicles\Views\modulesVehiculos/modalCaptureVehiculos') ?>

<?= $this->endSection() ?>