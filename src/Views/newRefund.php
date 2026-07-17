<?= $this->include('julio101290\boilerplate\Views\load/toggle') ?>
<?= $this->include('julio101290\boilerplate\Views\load\select2') ?>
<?= $this->include('julio101290\boilerplate\Views\load\datatables') ?>
<?= $this->include('julio101290\boilerplate\Views\load\nestable') ?>
<?= $this->include('julio101290\boilerplateproducts\Views\load\zoom') ?>
<!-- Extend from layout index -->
<?= $this->extend('julio101290\boilerplate\Views\layout\index') ?>

<!-- Section content -->
<?= $this->section('content') ?>

<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesRefunds/dataHeadRefunds') ?>
<?= $this->include('julio101290\boilerplateservicelayer\Views\modulesRefunds/moreInfoRow') ?>

<?= $this->endSection() ?>
