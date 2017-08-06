<?php
/**
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2016-2017 Carlos Garcia Gomez      neorazorx@gmail.com
 * Copyright (C) 2016      Luismipr                 luismipr@gmail.com
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
 * Description of articulos_compras
 *
 * @author Luismipr <luismipr@gmail.com>
 */
class articulos_pedidos extends fs_controller
{

    public $compras;
    public $pedidocli;
    public $pedidoprov;
    public $resultados;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Artículos Pedidos', 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        /// Cargamos extensiones
        $this->share_extensions();

        $this->compras = isset($_GET['compras']);
        $this->pedidocli = new pedido_cliente();
        $this->pedidoprov = new pedido_proveedor();

        /// Mostramos resultados
        $this->resultados = $this->buscar_articulos();
    }

    public function share_extensions()
    {
        //botón articulos pendientes de recibir en copras_pedidos
        $fsext = new fs_extension();
        $fsext->name = 'articulos_pedidos_compras';
        $fsext->from = __CLASS__;
        $fsext->to = 'compras_pedidos';
        $fsext->type = 'button';
        $fsext->text = '<span class="glyphicon glyphicon-tasks" aria-hidden="true"></span><span class="hidden-xs">&nbsp; Artículos</span>';
        $fsext->params = '&compras=TRUE';
        $fsext->save();

        //botón articulos pendientes de recibir en ventas_pedidos
        $fsext1 = new fs_extension();
        $fsext1->name = 'articulos_pedidos_ventas';
        $fsext1->from = __CLASS__;
        $fsext1->to = 'ventas_pedidos';
        $fsext1->type = 'button';
        $fsext1->text = '<span class="glyphicon glyphicon-tasks" aria-hidden="true"></span><span class="hidden-xs">&nbsp; Artículos</span>';
        $fsext1->save();
    }

    public function buscar_articulos()
    {
        $artlist = array();
        $art0 = new articulo();

        /// compras
        if ($this->db->table_exists('lineaspedidosprov')) {
            $sql = "SELECT sum(cantidad) as cantidadcompras,referencia,lineaspedidosprov.idpedido "
                . "FROM lineaspedidosprov LEFT JOIN pedidosprov ON lineaspedidosprov.idpedido = pedidosprov.idpedido "
                . "WHERE pedidosprov.idalbaran IS NULL AND editable AND lineaspedidosprov.referencia IS NOT NULL "
                . "GROUP BY referencia,lineaspedidosprov.idpedido "
                . "ORDER BY idpedido DESC, referencia ASC";
            $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, 0);
            if ($data) {
                foreach ($data as $d) {
                    $articulo = $art0->get($d['referencia']);
                    if ($articulo) {
                        if (isset($artlist[$articulo->referencia])) {
                            $artlist[$articulo->referencia]['cantidadcompras'] += floatval($d['cantidadcompras']);
                        } else {
                            $artlist[$articulo->referencia] = array(
                                'referencia' => $d['referencia'],
                                'cantidadcompras' => floatval($d['cantidadcompras']),
                                'cantidadventas' => 0,
                                'descripcion' => $articulo->descripcion,
                                'stockfisico' => $articulo->stockfis,
                                'pedidoscompras' => array(),
                                'pedidosventas' => array(),
                            );
                        }

                        $pedido = $this->pedidoprov->get($d['idpedido']);
                        if ($pedido) {
                            if ($pedido->idpedido == $d['idpedido']) {
                                $pedido->cantidadpedido = $d['cantidadcompras'];
                            }

                            $artlist[$articulo->referencia]['pedidoscompras'][] = $pedido;
                        }
                    }
                }
            }
        }

        /// ventas
        if ($this->db->table_exists('lineaspedidoscli')) {
            $sql1 = "SELECT sum(cantidad) as cantidadventas,referencia,lineaspedidoscli.idpedido "
                . "FROM lineaspedidoscli LEFT JOIN pedidoscli ON lineaspedidoscli.idpedido = pedidoscli.idpedido "
                . "WHERE pedidoscli.idalbaran IS NULL AND status = '0' "
                . "AND lineaspedidoscli.referencia IS NOT NULL AND lineaspedidoscli.referencia != '' "
                . "GROUP BY referencia,lineaspedidoscli.idpedido "
                . "ORDER BY idpedido DESC, referencia ASC";
            $data1 = $this->db->select_limit($sql1, FS_ITEM_LIMIT, 0);
            if ($data1) {
                foreach ($data1 as $d1) {
                    $articulo = $art0->get($d1['referencia']);
                    if ($articulo) {
                        if (isset($artlist[$articulo->referencia])) {
                            $artlist[$articulo->referencia]['cantidadventas'] += floatval($d1['cantidadventas']);
                        } else {
                            $artlist[$articulo->referencia] = array(
                                'referencia' => $d1['referencia'],
                                'cantidadcompras' => 0,
                                'cantidadventas' => floatval($d1['cantidadventas']),
                                'descripcion' => $articulo->descripcion,
                                'stockfisico' => $articulo->stockfis,
                                'pedidoscompras' => array(),
                                'pedidosventas' => array(),
                            );
                        }

                        $pedido1 = $this->pedidocli->get($d1['idpedido']);
                        if ($pedido1) {
                            if ($pedido1->idpedido == $d1['idpedido']) {
                                $pedido1->cantidadpedido = $d1['cantidadventas'];
                            }

                            $artlist[$articulo->referencia]['pedidosventas'][] = $pedido1;
                        }
                    }
                }
            }
        }

        /// ordenamos para poner primero los que no hay suficiente stock
        usort($artlist, function($a, $b) {
            if ($a['stockfisico'] + $a['cantidadcompras'] - $a['cantidadventas'] == $b['stockfisico'] + $b['cantidadcompras'] - $b['cantidadventas']) {
                return 0;
            } else if ($a['stockfisico'] + $a['cantidadcompras'] - $a['cantidadventas'] < $b['stockfisico'] + $b['cantidadcompras'] - $b['cantidadventas']) {
                return -1;
            } else
                return 1;
        });

        return $artlist;
    }
}
