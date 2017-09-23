<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017  Francesc Pineda Segarra  shawe.ewahs@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/facturacion_base/controller/ventas_imprimir.php';

/**
 * Esta clase agrupa los procedimientos de imprimir/enviar presupuestos y pedidos.
 */
class imprimir_presu_pedi extends ventas_imprimir
{

    public function __construct($name = __CLASS__, $title = 'imprimir', $folder = 'ventas')
    {
        parent::__construct($name, $title, $folder);
    }

    protected function private_core()
    {
        $this->init();

        if (isset($_REQUEST['pedido_p']) && isset($_REQUEST['id'])) {
            $ped = new pedido_proveedor();
            $this->documento = $ped->get($_REQUEST['id']);
            if ($this->documento) {
                $proveedor = new proveedor();
                $this->proveedor = $proveedor->get($this->documento->codproveedor);
            }

            if (isset($_POST['email'])) {
                $this->enviar_email_proveedor('pedido');
            } else {
                $this->generar_pdf_pedido_proveedor();
            }
        } else if (isset($_REQUEST['pedido']) && isset($_REQUEST['id'])) {
            $ped = new pedido_cliente();
            $this->documento = $ped->get($_REQUEST['id']);
            if ($this->documento) {
                $cliente = new cliente();
                $this->cliente = $cliente->get($this->documento->codcliente);
            }

            if (isset($_POST['email'])) {
                $this->enviar_email('pedido');
            } else {
                $this->generar_pdf_pedido();
            }
        } else if (isset($_REQUEST['presupuesto']) && isset($_REQUEST['id'])) {
            $pres = new presupuesto_cliente();
            $this->documento = $pres->get($_REQUEST['id']);
            if ($this->documento) {
                $cliente = new cliente();
                $this->cliente = $cliente->get($this->documento->codcliente);
            }

            if (isset($_POST['email'])) {
                $this->enviar_email('presupuesto');
            } else {
                $this->generar_pdf_presupuesto();
            }
        }
    }

    protected function share_extensions()
    {
        $extensiones = array(
            array(
                'name' => 'imprimir_pedido_proveedor',
                'page_from' => __CLASS__,
                'page_to' => 'compras_pedido',
                'type' => 'pdf',
                'text' => ucfirst(FS_PEDIDO) . ' simple',
                'params' => '&pedido_p=TRUE'
            ),
            array(
                'name' => 'email_pedido_proveedor',
                'page_from' => __CLASS__,
                'page_to' => 'compras_pedido',
                'type' => 'email',
                'text' => ucfirst(FS_PEDIDO) . ' simple',
                'params' => '&pedido_p=TRUE'
            ),
            array(
                'name' => 'imprimir_pedido',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_pedido',
                'type' => 'pdf',
                'text' => '<span class="glyphicon glyphicon-print"></span>&nbsp; ' . ucfirst(FS_PEDIDO) . ' simple',
                'params' => '&pedido=TRUE'
            ),
            array(
                'name' => 'imprimir_pedido_noval',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_pedido',
                'type' => 'pdf',
                'text' => '<span class="glyphicon glyphicon-print"></span>&nbsp; ' . ucfirst(FS_PEDIDO) . ' simple sin valorar',
                'params' => '&pedido=TRUE&noval=TRUE'
            ),
            array(
                'name' => 'email_pedido',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_pedido',
                'type' => 'email',
                'text' => ucfirst(FS_PEDIDO) . ' simple',
                'params' => '&pedido=TRUE'
            ),
            array(
                'name' => 'imprimir_presupuesto',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_presupuesto',
                'type' => 'pdf',
                'text' => '<span class="glyphicon glyphicon-print"></span>&nbsp; ' . ucfirst(FS_PRESUPUESTO) . ' simple',
                'params' => '&presupuesto=TRUE'
            ),
            array(
                'name' => 'email_presupuesto',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_presupuesto',
                'type' => 'email',
                'text' => ucfirst(FS_PRESUPUESTO) . ' simple',
                'params' => '&presupuesto=TRUE'
            )
        );
        foreach ($extensiones as $ext) {
            $fsext = new fs_extension($ext);
            if (!$fsext->save()) {
                $this->new_error_msg('Error al guardar la extensión ' . $ext['name']);
            }
        }
    }

    public function generar_pdf_presupuesto($archivo = FALSE)
    {
        if (!$archivo) {
            /// desactivamos la plantilla HTML
            $this->template = FALSE;
        }

        $pdf_doc = new fs_pdf();
        $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PRESUPUESTO) . ' ' . $this->documento->codigo);
        $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PRESUPUESTO) . ' de cliente ' . $this->documento->codigo);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

        $lineas = $this->documento->get_lineas();
        $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
        if ($lineas) {
            $linea_actual = 0;
            $pagina = 1;

            /// imprimimos las páginas necesarias
            while ($linea_actual < count($lineas)) {
                $lppag = 35;

                /// salto de página
                if ($linea_actual > 0) {
                    $pdf_doc->pdf->ezNewPage();
                }

                $pdf_doc->generar_pdf_cabecera($this->empresa, $lppag);
                $this->generar_pdf_datos_cliente($pdf_doc, $lppag);
                $this->generar_pdf_lineas_venta($pdf_doc, $lineas, $linea_actual, $lppag);

                /// ¿Fecha de validez?
                if ($linea_actual == count($lineas)) {
                    if ($this->documento->finoferta) {
                        $texto_pago = "\n<b>" . ucfirst(FS_PRESUPUESTO) . ' válido hasta:</b> ' . $this->documento->finoferta;
                        $pdf_doc->pdf->ezText($texto_pago, 9);
                    }

                    if ($this->impresion['print_formapago']) {
                        $this->generar_pdf_forma_pago($pdf_doc);
                    }
                }

                $pdf_doc->set_y(80);
                $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);
                $pagina++;
            }
        } else {
            $pdf_doc->pdf->ezText('¡' . ucfirst(FS_PRESUPUESTO) . ' sin líneas!', 20);
        }

        if ($archivo) {
            if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar')) {
                mkdir('tmp/' . FS_TMP_NAME . 'enviar');
            }

            $pdf_doc->save('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        } else {
            $pdf_doc->show(FS_PRESUPUESTO . '_' . $this->documento->codigo . '.pdf');
        }
    }

    public function generar_pdf_pedido_proveedor($archivo = FALSE)
    {
        if (!$archivo) {
            /// desactivamos la plantilla HTML
            $this->template = FALSE;
        }

        $pdf_doc = new fs_pdf();
        $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PEDIDO) . ' ' . $this->documento->codigo);
        $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PEDIDO) . ' de proveedor ' . $this->documento->codigo);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

        $lineas = $this->documento->get_lineas();
        $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
        if ($lineas) {
            $linea_actual = 0;
            $pagina = 1;

            /// imprimimos las páginas necesarias
            while ($linea_actual < count($lineas)) {
                $lppag = 35;

                /// salto de página
                if ($linea_actual > 0) {
                    $pdf_doc->pdf->ezNewPage();
                }

                $pdf_doc->generar_pdf_cabecera($this->empresa, $lppag);
                $this->generar_pdf_datos_proveedor($pdf_doc);
                $this->generar_pdf_lineas($pdf_doc, $lineas, $linea_actual, $lppag);

                $pdf_doc->set_y(80);
                $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);
                $pagina++;
            }
        } else {
            $pdf_doc->pdf->ezText('¡' . ucfirst(FS_PEDIDO) . ' sin líneas!', 20);
        }

        if ($archivo) {
            if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar')) {
                mkdir('tmp/' . FS_TMP_NAME . 'enviar');
            }

            $pdf_doc->save('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        } else {
            $pdf_doc->show(FS_PEDIDO . '_compra_' . $this->documento->codigo . '.pdf');
        }
    }

    public function generar_pdf_pedido($archivo = FALSE)
    {
        if (!$archivo) {
            /// desactivamos la plantilla HTML
            $this->template = FALSE;
        }

        $pdf_doc = new fs_pdf();
        $pdf_doc->pdf->addInfo('Title', ucfirst(FS_PEDIDO) . ' ' . $this->documento->codigo);
        $pdf_doc->pdf->addInfo('Subject', ucfirst(FS_PEDIDO) . ' de cliente ' . $this->documento->codigo);
        $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);

        $lineas = $this->documento->get_lineas();
        $lineas_iva = $pdf_doc->get_lineas_iva($lineas);
        if ($lineas) {
            $linea_actual = 0;
            $pagina = 1;

            /// imprimimos las páginas necesarias
            while ($linea_actual < count($lineas)) {
                $lppag = 35;

                /// salto de página
                if ($linea_actual > 0) {
                    $pdf_doc->pdf->ezNewPage();
                }

                $pdf_doc->generar_pdf_cabecera($this->empresa, $lppag);
                $this->generar_pdf_datos_cliente($pdf_doc, $lppag);
                $this->generar_pdf_lineas_venta($pdf_doc, $lineas, $linea_actual, $lppag);

                $pdf_doc->set_y(80);
                $this->generar_pdf_totales($pdf_doc, $lineas_iva, $pagina);
                $pagina++;
            }
        } else {
            $pdf_doc->pdf->ezText('¡' . ucfirst(FS_PEDIDO) . ' sin líneas!', 20);
        }

        if ($archivo) {
            if (!file_exists('tmp/' . FS_TMP_NAME . 'enviar')) {
                mkdir('tmp/' . FS_TMP_NAME . 'enviar');
            }

            $pdf_doc->save('tmp/' . FS_TMP_NAME . 'enviar/' . $archivo);
        } else {
            $pdf_doc->show(FS_PEDIDO . '_' . $this->documento->codigo . '.pdf');
        }
    }
}
