<?php
/*
 * This file is part of presupuestos_y_presupuestos
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017         Itaca Software Libre contacta@itacaswl.com
 * Copyright (C) 2017         PC REDNET ( Luis Miguel Pérez romero ) luismi@pcrednet.com
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

class informe_presupuestos extends informe_albaranes
{

    public $estado;

    public function __construct()
    {
        parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTOS), 'informes', FALSE, TRUE);
    }

    protected function private_core()
    {
        /// declaramos los objetos sólo para asegurarnos de que existen las tablas
        $presupuesto_cli = new presupuesto_cliente();

        $this->nombre_docs = FS_PRESUPUESTOS;
        $this->table_compras = 'presupuestoscli';
        $this->table_ventas = 'presupuestoscli';

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
            if ($this->estado == '0') {
                $this->where_ventas .= " AND idpedido is NULL AND status = 0";
            } else if ($this->estado == '1') {
                $this->where_ventas .= " AND status = '1'";
            } else if ($this->estado == '2') {
                $this->where_ventas .= " AND status = '2'";
            }
        }
    }

    public function stats_series($tabla = 'presupuestoscli')
    {
        return parent::stats_series($tabla);
    }

    public function stats_agentes($tabla = 'presupuestoscli')
    {
        return parent::stats_agentes($tabla);
    }

    public function stats_almacenes($tabla = 'presupuestoscli')
    {
        return parent::stats_almacenes($tabla);
    }

    public function stats_formas_pago($tabla = 'presupuestoscli')
    {
        return parent::stats_formas_pago($tabla);
    }

    public function stats_estados($tabla = 'presupuestoscli')
    {
        $stats = array();

        $sql = "select status,sum(neto) as total from presupuestoscli ";
        $sql .= $this->where_ventas;
        $sql .= " group by status order by total desc;";

        $data = $this->db->select($sql);
        if ($data) {
            $estados = array(
                0 => 'Pendientes',
                1 => 'Aprobados',
                2 => 'Rechazados',
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

    /**
     * 
     * @return type array datos
     */
    public function stats_clientes()
    {
        $stats = array();

        $sql = "select codcliente,sum(neto) as total from presupuestoscli";
        $sql .= $this->where_ventas;
        $sql .= " group by codcliente order by total desc;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                if (is_null($d['codcliente'])) {
                    $stats[] = array(
                        'txt' => 'Ninguno',
                        'total' => round(floatval($d['total']), FS_NF0)
                    );
                } else {
                    $cli0 = new cliente();
                    $cliente = $cli0->get($d['codcliente']);
                    if ($cliente) {
                        $stats[] = array(
                            'txt' => $cliente->nombre,
                            'total' => round(floatval($d['total']), FS_NF0)
                        );
                    } else {
                        $stats[] = array(
                            'txt' => $d['codcliente'],
                            'total' => round(floatval($d['total']), FS_NF0)
                        );
                    }
                }
            }
        }

        return $stats;
    }

    protected function get_documentos($tabla)
    {
        $doclist = array();

        $sql = "select * from " . $tabla . $this->where_ventas . " order by fecha asc, hora asc;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $doclist[] = new presupuesto_cliente($d);
            }
        }

        return $doclist;
    }
}
