<?php

namespace julio101290\boilerplateservicelayer\Libraries;

use Exception;

class QuoteTemplateSecurity
{
    /**
     * Directorio base donde residen las plantillas editables
     */
    public static function getTemplatesDir(): string
    {
        $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'quotes' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        self::ensureDefaultTemplate($dir);
        return $dir;
    }

    /**
     * Lista únicamente los archivos .html válidos y seguros dentro de writable/templates/quotes/
     */
    public static function listAvailableTemplates(): array
    {
        $dir   = self::getTemplatesDir();
        $files = glob($dir . '*.html') ?: [];
        $list  = [];

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $check    = self::validateTemplate($fileName);
            if ($check['valid']) {
                $list[] = [
                    'id'   => $fileName,
                    'text' => $fileName
                ];
            }
        }

        return $list;
    }

    /**
     * Valida que el archivo de plantilla exista, no tenga Path Traversal
     * y su código HTML no contenga inyecciones ni etiquetas peligrosas para TCPDF/PHP.
     */
    public static function validateTemplate(string $templateFileName): array
    {
        $dir       = self::getTemplatesDir();
        $cleanName = trim($templateFileName);

        if ($cleanName === '') {
            return ['valid' => false, 'error' => 'El nombre de la plantilla no puede estar vacío.'];
        }

        // 1. Validar formato estricto del nombre de archivo (sin barras, ni puntos dobles, solo .html)
        if (!preg_match('/^[a-zA-Z0-9_\-]+\.html$/', $cleanName)) {
            return [
                'valid' => false,
                'error' => 'Nombre de plantilla inválido. Solo se permiten letras, números, guiones y extensión .html.'
            ];
        }

        $fullPath = $dir . $cleanName;
        $realDir  = realpath($dir);
        $realFile = realpath($fullPath);

        // 2. Prevenir Path Traversal (el archivo debe existir dentro de writable/templates/quotes/)
        if ($realFile === false || $realDir === false || strpos($realFile, $realDir) !== 0 || !is_file($realFile)) {
            return [
                'valid' => false,
                'error' => "La plantilla '{$cleanName}' no existe en writable/templates/quotes/."
            ];
        }

        // 3. Limitar tamaño máximo (máximo 1 MB para evitar denegación de servicio en TCPDF)
        if (filesize($realFile) > 1048576) {
            return [
                'valid' => false,
                'error' => "La plantilla '{$cleanName}' excede el tamaño máximo permitido de 1 MB."
            ];
        }

        $content = file_get_contents($realFile);
        if ($content === false) {
            return [
                'valid' => false,
                'error' => "No se pudo leer el archivo de plantilla '{$cleanName}'."
            ];
        }

        // 4. Reglas de inspección de seguridad del contenido
        $forbiddenPatterns = [
            '/<\?php/i'                         => 'Etiquetas de código PHP detectadas.',
            '/<\?=/i'                           => 'Etiquetas cortas de impresión PHP detectadas.',
            '/<\?/i'                            => 'Apertura de etiqueta PHP detectada.',
            '/\?>/i'                            => 'Cierre de etiqueta PHP detectado.',
            '/<\s*tcpdf\b/i'                    => 'Etiqueta <tcpdf> prohibida por seguridad (ejecución de métodos).',
            '/<\s*script\b/i'                   => 'Etiqueta <script> no permitida en plantillas PDF.',
            '/<\s*iframe\b/i'                   => 'Etiqueta <iframe> no permitida en plantillas PDF.',
            '/<\s*object\b/i'                   => 'Etiqueta <object> no permitida en plantillas PDF.',
            '/<\s*embed\b/i'                    => 'Etiqueta <embed> no permitida en plantillas PDF.',
            '/<\s*form\b/i'                     => 'Etiqueta <form> no permitida en plantillas PDF.',
            '/<\s*link\b/i'                     => 'Etiqueta <link> externa no permitida.',
            '/<\s*meta\b/i'                     => 'Etiqueta <meta> no permitida.',
            '/\bon[a-z]+\s*=/i'                 => 'Atributos de eventos JavaScript (onclick, onload, etc.) no permitidos.',
            '/(javascript|vbscript|data)\s*:/i' => 'Protocolos de ejecución en línea (javascript:/data:) no permitidos.',
            '/(phar|php|expect|file|glob|zip)\s*:\/\//i' => 'Wrappers de flujo (phar://, php://, file://) prohibidos por seguridad.',
            '/(src|href)\s*=\s*["\']\s*https?:\/\//i'    => 'Recursos remotos HTTP/HTTPS externos prohibidos (previene SSRF).'
        ];

        foreach ($forbiddenPatterns as $regex => $motivo) {
            if (preg_match($regex, $content)) {
                return [
                    'valid' => false,
                    'error' => "Problema de seguridad en '{$cleanName}': {$motivo}"
                ];
            }
        }

        return [
            'valid'    => true,
            'path'     => $realFile,
            'fileName' => $cleanName,
            'content'  => $content
        ];
    }

    /**
     * Crea la plantilla base quote_pdf_template.html si no existe
     */
    private static function ensureDefaultTemplate(string $dir): void
    {
        $defaultFile = $dir . 'quote_pdf_template.html';
        if (is_file($defaultFile)) {
            return;
        }

        $defaultContent = <<<'HTML'
<table cellspacing="0" style="width: 100%;">
    <tr>
        <td style="width:150px">{{LOGO_HEADER_HTML}}</td>
        <td style="width: 50%; color: #34495e; font-size:12px; text-align:center">
            <div style="font-size:8px; text-align:center;">
                <span style="color: black; font-size:12px; font-weight:bold">
                    {{NOMBRE_EMPRESA}}
                </span>
                <br>
                {{DIRECCION_SUCURSAL}}
                <br>
                Telefono(s): {{TELEFONOS_SUCURSAL}}
            </div>
        </td>
        <td style="background-color:white; width:100px; text-align:center; color:black; font-size:12px; font-weight:bold">
            <br>COTIZACIÓN<br>
            <span style="color:red; font-weight:bold;">{{FOLIO_COTIZACION}}</span>
        </td>
    </tr>
</table>

<!-- BLOCK_SEPARATOR -->

<div style="font-size:8pt; text-align:right; font-weight:bold">{{CIUDAD_SUCURSAL}} a {{FECHA_HUMANIZADA}}</div>
<br>
<div style="font-size:8pt; text-align:left; font-weight:bold">Por medio de la presente, ponemos a su consideración la siguiente propuesta de precios de los materiales solicitados.</div>
<br>
<table style="font-size:8px; padding:0px 8px;">
    <tr>
        <td style="width: 50%; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white;">ATENCION A</td>
        <td style="width: 50%; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white;">OBSERVACIONES</td>
    </tr>
    <tr>
        <td>
            Cliente: {{NOMBRE_CLIENTE}}<br>
            Telefono: {{TELEFONO_CLIENTE}}<br>
            E-Mail: {{EMAIL_CLIENTE}}<br>
        </td>
        <td>
            {{OBSERVACIONES}}<br>
            Código Postal: {{CODIGO_POSTAL}}<br>
            Metodo de Pago: {{METODO_PAGO}}<br>
            Uso de CFDI: {{USO_CFDI}}<br>
            Forma de Pago: {{FORMA_PAGO}}
        </td>
    </tr>
    <tr>
        <td style="width: 35%; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white;">VENDEDOR</td>
        <td style="width: 14%; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white;">FECHA</td>
        <td style="width: 14%; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white;">VIGENCIA</td>
        <td style="width: 37%; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white;">CONTACTO / OBRA</td>
    </tr>
    <tr>
        <td>{{NOMBRE_VENDEDOR}}</td>
        <td>{{FECHA}}</td>
        <td>{{FECHA_VENCIMIENTO}}</td>
        <td>{{COTIZAR_A}}</td>
    </tr>
    <tr>
        <td colspan="4" style="border-bottom: 1px solid #666; background-color:white; width:540px"></td>
    </tr>
</table>

<!-- BLOCK_SEPARATOR -->

<table style="font-size:8px; padding:5px 10px;">
    <tr>
        <td style="width: 110px; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white; text-align:center">Codigo</td>
        <td style="width: 150px; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white; text-align:center">Producto</td>
        <td style="width: 40px; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white; text-align:center">Cant.</td>
        <td style="width: 80px; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white; text-align:center">Unidad</td>
        <td style="width: 80px; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white; text-align:center">Valor Unit.</td>
        <td style="width: 80px; background-color:{{COLOR_PRIMARIO}}; padding: 4px 4px 4px; font-weight:bold; color:white; text-align:center">Valor Total</td>
    </tr>
</table>

<!-- BLOCK_SEPARATOR -->

{{BLOQUE_PRODUCTOS}}

<!-- BLOCK_SEPARATOR -->

<table style="font-size:8px; padding:5px 10px;">
    <tr>
        <td style="font-size:6px; width:340px; text-align:left" rowspan="6"></td>
        <td style="border-bottom: 0px solid #666; background-color:white; width:100px; text-align:right"></td>
        <td style="border-bottom: 0px solid #666; color:#333; background-color:white; width:100px; text-align:right"></td>
    </tr>
    <tr>
        <td style="background-color:#ecf0f1; border: 0px solid #666; width:100px; text-align:right">Subtotal:</td>
        <td style="border: 0px solid #666; color:#333; background-color:white; width:100px; text-align:right">{{SUBTOTAL}}</td>
    </tr>
    <tr>
        <td style="border: 0px solid #666; background-color:#ecf0f1; width:100px; text-align:right">IVA:</td>
        <td style="border: 0px solid #666; color:#333; background-color:white; width:100px; text-align:right">{{IVA}}</td>
    </tr>
    <tr>
        <td style="border: 0px solid #666; background-color:#ecf0f1; width:100px; text-align:right">Total:</td>
        <td style="border: 0px solid #666; color:#333; background-color:white; width:100px; text-align:right">$ {{TOTAL}}</td>
    </tr>
    <tr><td></td><td></td></tr>
    <tr><td></td><td></td></tr>
</table>

<div style="font-size:8pt; text-align:center; font-weight:bold">
    Los pagos deben ser realizados exclusivamente a través de las cuentas bancarias oficiales de {{NOMBRE_EMPRESA}}, la empresa no se hace responsable por pagos a nombre de terceros {{NO_REFACTURACION}}
</div>
<div style="font-size:8pt; text-align:center; font-weight:bold">
    Sin mas por el momento quedo a sus ordenes y en espera de que nos de la oportunidad de trabajar juntos!
</div>
<div style="font-size:8pt; text-align:center; font-weight:bold">
    Atentamente<br>
    {{NOMBRE_VENDEDOR}}<br>
    Tel. {{TELEFONO_VENDEDOR}}<br>
    {{PUESTO_VENDEDOR}} {{CORREO_VENDEDOR_HTML}}
</div>

<table border="0" style="width: 100%;">
    <tr>
        <td style="font-size:6px; text-align:left; width: 78%;">
            {{LEYENDA_SUCURSAL}}
        </td>
        <td style="text-align:right; width: 22%;">
            {{LOGO_CALIDAD_HTML}}
        </td>
    </tr>
</table>
HTML;
        file_put_contents($defaultFile, $defaultContent);
    }
}