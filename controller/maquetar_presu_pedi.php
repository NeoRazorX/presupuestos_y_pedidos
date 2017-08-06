<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2016  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * Description of maquetar_presu_pedi
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class maquetar_presu_pedi extends fs_controller
{

    public $documento;
    public $lineas;
    public $titulo;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Maquetar', 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        $this->share_extensions();

        $this->documento = FALSE;
        if (isset($_REQUEST['presu'])) {
            $pre0 = new presupuesto_cliente();
            $this->documento = $pre0->get($_REQUEST['id']);
            if ($this->documento) {
                $this->titulo = FS_PRESUPUESTO . ' ' . $this->documento->codigo;
                $this->lineas = $this->documento->get_lineas();

                if (isset($_POST['idlinea'])) {
                    if ($this->documento->editable) {
                        $orden = 1 + count($_POST['idlinea']);
                        foreach ($_POST['idlinea'] as $idl) {
                            foreach ($this->lineas as $lin) {
                                if ($lin->idlinea == $idl) {
                                    $lin->orden = $orden;

                                    $lin->mostrar_cantidad = FALSE;
                                    $lin->mostrar_precio = FALSE;
                                    if (isset($_POST['mostrar_cantidad_' . $idl])) {
                                        $lin->mostrar_cantidad = TRUE;
                                        $lin->mostrar_precio = isset($_POST['mostrar_precio_' . $idl]);
                                    }

                                    $lin->save();
                                    break;
                                }
                            }

                            $orden--;
                        }

                        $this->new_message('Datos guardados correctamente.');
                        $this->lineas = $this->documento->get_lineas();
                    } else {
                        $this->new_error_msg('El documento ya no es editable.');
                    }
                }
            }
        } else if (isset($_REQUEST['pedido'])) {
            $ped0 = new pedido_cliente();
            $this->documento = $ped0->get($_REQUEST['id']);
            if ($this->documento) {
                $this->titulo = FS_PEDIDO . ' ' . $this->documento->codigo;
                $this->lineas = $this->documento->get_lineas();

                if (isset($_POST['idlinea'])) {
                    if ($this->documento->editable) {
                        $orden = 1 + count($_POST['idlinea']);
                        foreach ($_POST['idlinea'] as $idl) {
                            foreach ($this->lineas as $lin) {
                                if ($lin->idlinea == $idl) {
                                    $lin->orden = $orden;

                                    $lin->mostrar_cantidad = FALSE;
                                    $lin->mostrar_precio = FALSE;
                                    if (isset($_POST['mostrar_cantidad_' . $idl])) {
                                        $lin->mostrar_cantidad = TRUE;
                                        $lin->mostrar_precio = isset($_POST['mostrar_precio_' . $idl]);
                                    }

                                    $lin->save();
                                    break;
                                }
                            }

                            $orden--;
                        }

                        $this->new_message('Datos guardados correctamente.');
                        $this->lineas = $this->documento->get_lineas();
                    } else {
                        $this->new_error_msg('El documento ya no es editable.');
                    }
                }
            }
        }
    }

    private function share_extensions()
    {
        $fsext = new fs_extension();
        $fsext->name = 'maquetar_presu';
        $fsext->from = __CLASS__;
        $fsext->to = 'ventas_presupuesto';
        $fsext->type = 'pdf';
        $fsext->text = '<i class="fa fa-magic"></i>&nbsp; Maquetar';
        $fsext->params = '&presu=TRUE';
        $fsext->save();

        $fsext2 = new fs_extension();
        $fsext2->name = 'maquetar_pedido';
        $fsext2->from = __CLASS__;
        $fsext2->to = 'ventas_pedido';
        $fsext2->type = 'pdf';
        $fsext2->text = '<i class="fa fa-magic"></i>&nbsp; Maquetar';
        $fsext2->params = '&pedido=TRUE';
        $fsext2->save();
    }

    public function url()
    {
        switch (get_class_name($this->documento)) {
            case 'presupuesto_cliente':
                return 'index.php?page=' . __CLASS__ . '&presu=TRUE&id=' . $this->documento->idpresupuesto;
                break;

            case 'pedido_cliente':
                return 'index.php?page=' . __CLASS__ . '&pedido=TRUE&id=' . $this->documento->idpedido;
                break;

            default:
                return parent::url();
                break;
        }
    }
}
