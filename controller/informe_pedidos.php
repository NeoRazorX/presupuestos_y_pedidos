<?php
/*
 * This file is part of pedidos_y_pedidos
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017         Itaca Software Libre contacta@itacaswl.com
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

require_once 'plugins/facturacion_base/controller/informe_albaranes.php';

/**
 * Heredamos del controlador de informe_albaranes, para reaprovechar el código.
 */
class informe_pedidos extends informe_albaranes
{

    public $estado;

    public function __construct()
    {
        parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'informes');
    }

    protected function private_core()
    {
        /// declaramos los objetos sólo para asegurarnos de que existen las tablas
        $pedido_cli = new pedido_cliente();
        $pedido_pro = new pedido_proveedor();

        $this->nombre_docs = FS_PEDIDOS;
        $this->table_compras = 'pedidosprov';
        $this->table_ventas = 'pedidoscli';

        parent::private_core();
    }

    protected function ini_filters()
    {
        parent::ini_filters();

        $this->estado = '';
        if (isset($_REQUEST['estado'])) {
            $this->estado = $_REQUEST['estado'];
        }
    }

    protected function set_where()
    {
        parent::set_where();

        if ($this->estado != '') {
            switch ($this->estado) {
                case '0':
                    $this->where_compras .= " AND idalbaran IS NULL";
                    $this->where_ventas .= " AND idalbaran IS NULL AND status = '0'";
                    break;

                case '1':
                    $this->where_compras .= " AND idalbaran IS NOT NULL";
                    $this->where_ventas .= " AND status = '1'";
                    break;

                case '2':
                    $this->where_compras .= " AND 1 = 2";
                    $this->where_ventas .= " AND status = '2'";
                    break;
            }
        }
    }

    public function stats_series($tabla = 'pedidosprov')
    {
        return parent::stats_series($tabla);
    }

    public function stats_agentes($tabla = 'pedidosprov')
    {
        return parent::stats_agentes($tabla);
    }

    public function stats_almacenes($tabla = 'pedidosprov')
    {
        return parent::stats_almacenes($tabla);
    }

    public function stats_formas_pago($tabla = 'pedidosprov')
    {
        return parent::stats_formas_pago($tabla);
    }

    public function stats_estados($tabla = 'pedidosprov')
    {
        $stats = array();

        if ($tabla == 'pedidoscli') {
            $stats = $this->stats_estados_pedidoscli();
        } else {
            /// aprobados
            $sql = "select sum(neto) as total from " . $tabla;
            $sql .= $this->where_compras;
            $sql .= " and idalbaran is not null order by total desc;";

            $data = $this->db->select($sql);
            if ($data) {
                if (floatval($data[0]['total'])) {
                    $stats[] = array(
                        'txt' => 'aprobado',
                        'total' => round(floatval($data[0]['total']), FS_NF0)
                    );
                }
            }

            /// pendientes
            $sql = "select sum(neto) as total from " . $tabla;
            $sql .= $this->where_compras;
            $sql .= " and idalbaran is null order by total desc;";

            $data = $this->db->select($sql);
            if ($data) {
                if (floatval($data[0]['total'])) {
                    $stats[] = array(
                        'txt' => 'pendiente',
                        'total' => round(floatval($data[0]['total']), FS_NF0)
                    );
                }
            }
        }

        return $stats;
    }

    private function stats_estados_pedidoscli()
    {
        $stats = array();
        $tabla = 'pedidoscli';

        $sql = "select status,sum(neto) as total from " . $tabla;
        $sql .= $this->where_ventas;
        $sql .= " group by status order by total desc;";

        $data = $this->db->select($sql);
        if ($data) {
            $estados = array(
                0 => 'pendiente',
                1 => 'aprobado',
                2 => 'rechazado',
                3 => 'validado parcialmente'
            );

            foreach ($data as $d) {
                $stats[] = array(
                    'txt' => $estados[$d['status']],
                    'total' => round(floatval($d['total']), FS_NF0)
                );
            }
        }

        return $stats;
    }

    protected function get_documentos($tabla)
    {
        $doclist = array();

        $where = $this->where_compras;
        if ($tabla == $this->table_ventas) {
            $where = $this->where_ventas;
        }

        $sql = "select * from " . $tabla . $where . " order by fecha asc, hora asc;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                if ($tabla == $this->table_ventas) {
                    $doclist[] = new pedido_cliente($d);
                } else {
                    $doclist[] = new pedido_proveedor($d);
                }
            }
        }

        return $doclist;
    }
}
