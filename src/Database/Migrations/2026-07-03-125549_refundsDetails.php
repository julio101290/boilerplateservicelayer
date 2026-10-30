<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Refundsdetails extends Migration
{
    public function up()
    {
        // Tabla de detalle: Equivalente a @QSYS_GLO_VODE (QSYS_VouchersDetail) + Staging Service Layer
        $this->forge->addField([
            'id'                 => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'idRefund'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            
            // Campos nativos de tabla de usuario SAP (@QSYS_GLO_VODE)
            'code'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // RowCode en SAP
            'name'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_NA'               => ['type' => 'varchar', 'constraint' => 4, 'default' => 'N', 'null' => true], // 'Y' o 'N'
            'U_Line'             => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_Date'             => ['type' => 'date', 'null' => true],
            'U_Type'             => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // 'XML' o 'Nota'
            'U_CodeVoucher'      => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // RowCode del encabezado
            'U_DocEntry'         => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // DocEntry OPCH o TransId OJDT
            'U_DocNum'           => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'U_Provider'         => ['type' => 'varchar', 'constraint' => 128, 'null' => true], // CardCode o Nombre Proveedor en Nota
            'U_Status'           => ['type' => 'varchar', 'constraint' => 64, 'default' => 'Abierto', 'null' => true], // Abierto, Cerrado, Cancelado
            'U_Coments'          => ['type' => 'varchar', 'constraint' => 254, 'null' => true], // Observaciones de captura
            'U_Subtotal'         => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_IVA'              => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_RetIVA'           => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_ISR'              => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_IEPS'             => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_Total'            => ['type' => 'decimal', 'constraint' => '18,2', 'default' => 0.00, 'null' => true],
            'U_Coment'           => ['type' => 'varchar', 'constraint' => 254, 'null' => true], // Comentario de revisión (C_Coment)
            'U_UserCode'         => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            
            // Campos para Staging de Notas (frmPurchaseNotes) y XML (frmPurchaseXML) antes de enviar a SAP
            'docFolio'           => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // Folio de la nota o factura (C_DocFolio)
            'UUID'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'accountType'        => ['type' => 'varchar', 'constraint' => 32, 'null' => true], // 'Gastos/Costos' o 'Deudora'
            'account'            => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // Cuenta de gasto/deudora (C_Account)
            'affectable'         => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // Cuenta acreedora (C_Affect)
            'auxEmployee'        => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // Auxiliar (C_Aux)
            'auxAffectable'      => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // Auxiliar afectable (C_AuxAf)
            'codeMovLine'        => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // C_CodeMov en notas deudoras
            'project'            => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // PrjCode (C_Project)
            'adminOper'          => ['type' => 'char', 'constraint' => 1, 'default' => 'A', 'null' => true], // 'A' o 'O' (C_Admin)
            'whsCode'            => ['type' => 'varchar', 'constraint' => 64, 'null' => true], // Almacén (C_Whs)
            'dim1'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'dim2'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'dim3'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'dim4'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'dim5'               => ['type' => 'varchar', 'constraint' => 64, 'null' => true],
            'xmlFile'            => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'pdfFile'            => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'sapPaymentDocEntry' => ['type' => 'int', 'constraint' => 11, 'null' => true], // DocEntry del pago individual (DoPayments)
            
            // Campos estándar de tu boilerplate de detalle
            'description'        => ['type' => 'varchar', 'constraint' => 512, 'null' => true],
            'cant'               => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 1.0000, 'null' => true],
            'price'              => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'porcentTax'         => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'tax'                => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'porcentIVARetenido' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'IVARetenido'        => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'porcentISRRetenido' => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'ISRRetenido'        => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'neto'               => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'tasaCero'           => ['type' => 'varchar', 'constraint' => 16, 'null' => true],
            'importeExento'      => ['type' => 'decimal', 'constraint' => '18,4', 'default' => 0.0000, 'null' => true],
            'created_at'         => ['type' => 'datetime', 'null' => true],
            'updated_at'         => ['type' => 'datetime', 'null' => true],
            'deleted_at'         => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('idRefund');
        $this->forge->addKey('U_CodeVoucher');
        $this->forge->addKey('U_DocEntry');
        $this->forge->createTable('refundsdetails', true);
    }

    public function down()
    {
        $this->forge->dropTable('refundsdetails', true);
    }
}