<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

require_once 'plugins/facturacion_base/extras/fbase_controller.php';

class compras_pedidos extends fbase_controller
{

    public $agente;
    public $almacenes;
    public $articulo;
    public $buscar_lineas;
    public $codagente;
    public $codalmacen;
    public $codserie;
    public $desde;
    public $hasta;
    public $lineas;
    public $mostrar;
    public $num_resultados;
    public $offset;
    public $order;
    public $proveedor;
    public $resultados;
    public $serie;
    public $total_resultados;
    public $total_resultados_txt;

    public function __construct()
    {
        parent::__construct(__CLASS__, ucfirst(FS_PEDIDOS), 'compras');
    }

    protected function private_core()
    {
        parent::private_core();

        $pedido = new pedido_proveedor();
        $this->agente = new agente();
        $this->almacenes = new almacen();
        $this->serie = new serie();

        $this->mostrar = 'todo';
        if (isset($_GET['mostrar'])) {
            $this->mostrar = $_GET['mostrar'];
            setcookie('compras_ped_mostrar', $this->mostrar, time() + FS_COOKIES_EXPIRE);
        } else if (isset($_COOKIE['compras_ped_mostrar'])) {
            $this->mostrar = $_COOKIE['compras_ped_mostrar'];
        }

        $this->offset = 0;
        if (isset($_GET['offset'])) {
            $this->offset = intval($_GET['offset']);
        }

        $this->order = 'fecha DESC';
        if (isset($_GET['order'])) {
            $orden_l = $this->orden();
            if (isset($orden_l[$_GET['order']])) {
                $this->order = $orden_l[$_GET['order']]['orden'];
            }

            setcookie('compras_ped_order', $this->order, time() + FS_COOKIES_EXPIRE);
        } else if (isset($_COOKIE['compras_ped_order'])) {
            $this->order = $_COOKIE['compras_ped_order'];
        }

        if (isset($_POST['buscar_lineas'])) {
            $this->buscar_lineas();
        } else if (isset($_REQUEST['buscar_proveedor'])) {
            $this->fbase_buscar_proveedor($_REQUEST['buscar_proveedor']);
        } else if (isset($_GET['ref'])) {
            $this->template = 'extension/compras_pedidos_articulo';

            $articulo = new articulo();
            $this->articulo = $articulo->get($_GET['ref']);

            $linea = new linea_pedido_proveedor();
            $this->resultados = $linea->all_from_articulo($_GET['ref'], $this->offset);
        } else {
            $this->share_extension();
            $this->codagente = '';
            $this->codalmacen = '';
            $this->codserie = '';
            $this->desde = '';
            $this->hasta = '';
            $this->num_resultados = '';
            $this->proveedor = FALSE;
            $this->total_resultados = array();
            $this->total_resultados_txt = '';

            if (isset($_POST['delete'])) {
                $this->delete_pedido();
            } else {
                if (!isset($_GET['mostrar']) && ( $this->query != '' || isset($_REQUEST['codagente']) || isset($_REQUEST['codproveedor']) || isset($_REQUEST['codserie']))) {
                    /**
                     * si obtenermos un codagente, un codproveedor o un codserie pasamos direcatemente
                     * a la pestaña de búsqueda, a menos que tengamos un mostrar, que
                     * entonces nos indica donde tenemos que estar.
                     */
                    $this->mostrar = 'buscar';
                }

                if (isset($_REQUEST['codproveedor']) && $_REQUEST['codproveedor'] != '') {
                    $pro0 = new proveedor();
                    $this->proveedor = $pro0->get($_REQUEST['codproveedor']);
                }

                if (isset($_REQUEST['codagente'])) {
                    $this->codagente = $_REQUEST['codagente'];
                }

                if (isset($_REQUEST['codalmacen'])) {
                    $this->codalmacen = $_REQUEST['codalmacen'];
                }

                if (isset($_REQUEST['codserie'])) {
                    $this->codserie = $_REQUEST['codserie'];
                }

                if (isset($_REQUEST['desde'])) {
                    $this->desde = $_REQUEST['desde'];
                    $this->hasta = $_REQUEST['hasta'];
                }
            }

            /// añadimos segundo nivel de ordenación
            $order2 = '';
            if ($this->order == 'fecha DESC') {
                $order2 = ', hora DESC';
            } else if ($this->order == 'fecha ASC') {
                $order2 = ', hora ASC';
            }

            if ($this->mostrar == 'pendientes') {
                $this->resultados = $pedido->all_ptealbaran($this->offset, $this->order . $order2);

                if ($this->offset == 0) {
                    /// calculamos el total, pero desglosando por divisa
                    $this->total_resultados = array();
                    $this->total_resultados_txt = 'Suma total de esta página:';
                    foreach ($this->resultados as $doc) {
                        if (!isset($this->total_resultados[$doc->coddivisa])) {
                            $this->total_resultados[$doc->coddivisa] = array(
                                'coddivisa' => $doc->coddivisa,
                                'total' => 0
                            );
                        }

                        $this->total_resultados[$doc->coddivisa]['total'] += $doc->total;
                    }
                }
            } else if ($this->mostrar == 'buscar') {
                $this->buscar($order2);
            } else
                $this->resultados = $pedido->all($this->offset, $this->order . $order2);

            /**
             * Ejecutamos el proceso del cron para pedidos.
             * No es estrictamente necesario, pero viene bien para cuando el
             * cliente no tiene configurado el cron.
             */
            $pedido->cron_job();
        }
    }

    public function url($busqueda = FALSE)
    {
        if ($busqueda) {
            $codproveedor = '';
            if ($this->proveedor) {
                $codproveedor = $this->proveedor->codproveedor;
            }

            $url = parent::url() . "&mostrar=" . $this->mostrar
                . "&query=" . $this->query
                . "&codserie=" . $this->codserie
                . "&codagente=" . $this->codagente
                . "&codalmacen=" . $this->codalmacen
                . "&codproveedor=" . $codproveedor
                . "&desde=" . $this->desde
                . "&hasta=" . $this->hasta;

            return $url;
        }

        return parent::url();
    }

    public function paginas()
    {
        if ($this->mostrar == 'pendientes') {
            $total = $this->total_pendientes();
        } else if ($this->mostrar == 'buscar') {
            $total = $this->num_resultados;
        } else {
            $total = $this->total_registros();
        }

        return $this->fbase_paginas($this->url(TRUE), $total, $this->offset);
    }

    public function anterior_url()
    {
        if ($this->offset > 0) {
            return $this->url() . "&ref=" . $_GET['ref'] . "&offset=" . ($this->offset - FS_ITEM_LIMIT);
        }

        return '';
    }

    public function siguiente_url()
    {
        if (count($this->resultados) == FS_ITEM_LIMIT) {
            return parent::url() . "&ref=" . $_GET['ref'] . "&offset=" . ($this->offset + FS_ITEM_LIMIT);
        }

        return '';
    }

    public function buscar_lineas()
    {
        /// cambiamos la plantilla HTML
        $this->template = 'ajax/compras_lineas_pedidos';

        $this->buscar_lineas = $_POST['buscar_lineas'];
        $linea = new linea_pedido_proveedor();

        if (isset($_POST['codproveedor'])) {
            $this->lineas = $linea->search_from_proveedor($_POST['codproveedor'], $this->buscar_lineas, $this->offset);
        } else {
            $this->lineas = $linea->search($this->buscar_lineas, $this->offset);
        }
    }

    private function delete_pedido()
    {
        $ped0 = new pedido_proveedor();
        $pedido = $ped0->get($_POST['delete']);
        if ($pedido) {
            if ($pedido->delete()) {
                $this->clean_last_changes();
            } else {
                $this->new_error_msg("¡Imposible eliminar el " . FS_PEDIDO . "!");
            }
        } else {
            $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " no encontrado!");
        }
    }

    private function share_extension()
    {
        /// añadimos las extensiones para proveedors, agentes y artículos
        $extensiones = array(
            array(
                'name' => 'pedidos_proveedor',
                'page_from' => __CLASS__,
                'page_to' => 'compras_proveedor',
                'type' => 'button',
                'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst(FS_PEDIDOS),
                'params' => ''
            ),
            array(
                'name' => 'pedidos_agente',
                'page_from' => __CLASS__,
                'page_to' => 'admin_agente',
                'type' => 'button',
                'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst(FS_PEDIDOS) . ' a proveedor',
                'params' => ''
            ),
            array(
                'name' => 'pedidos_articulo',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_articulo',
                'type' => 'tab_button',
                'text' => '<span class="glyphicon glyphicon-list" aria-hidden="true"></span> &nbsp; ' . ucfirst(FS_PEDIDOS) . ' a proveedor',
                'params' => ''
            ),
        );
        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }

    public function total_pendientes()
    {
        return $this->fbase_sql_total('pedidosprov', 'idpedido', 'WHERE idalbaran IS NULL');
    }

    private function total_registros()
    {
        return $this->fbase_sql_total('pedidosprov', 'idpedido');
    }

    private function buscar($order2)
    {
        $this->resultados = array();
        $this->num_resultados = 0;
        $sql = " FROM pedidosprov ";
        $where = 'WHERE ';

        if ($this->query) {
            $query = $this->agente->no_html(mb_strtolower($this->query, 'UTF8'));
            $sql .= $where;
            if (is_numeric($query)) {
                $sql .= "(codigo LIKE '%" . $query . "%' OR numproveedor LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%')";
            } else {
                $sql .= "(lower(codigo) LIKE '%" . $query . "%' OR lower(numproveedor) LIKE '%" . $query . "%' "
                    . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%')";
            }
            $where = ' AND ';
        }

        if ($this->codagente) {
            $sql .= $where . "codagente = " . $this->agente->var2str($this->codagente);
            $where = ' AND ';
        }

        if ($this->codalmacen) {
            $sql .= $where . "codalmacen = " . $this->agente->var2str($this->codalmacen);
            $where = ' AND ';
        }

        if ($this->proveedor) {
            $sql .= $where . "codproveedor = " . $this->agente->var2str($this->proveedor->codproveedor);
            $where = ' AND ';
        }

        if ($this->codserie) {
            $sql .= $where . "codserie = " . $this->agente->var2str($this->codserie);
            $where = ' AND ';
        }

        if ($this->desde) {
            $sql .= $where . "fecha >= " . $this->agente->var2str($this->desde);
            $where = ' AND ';
        }

        if ($this->hasta) {
            $sql .= $where . "fecha <= " . $this->agente->var2str($this->hasta);
            $where = ' AND ';
        }

        $data = $this->db->select("SELECT COUNT(idpedido) as total" . $sql);
        if ($data) {
            $this->num_resultados = intval($data[0]['total']);

            $data2 = $this->db->select_limit("SELECT *" . $sql . " ORDER BY " . $this->order . $order2, FS_ITEM_LIMIT, $this->offset);
            if ($data2) {
                foreach ($data2 as $d) {
                    $this->resultados[] = new pedido_proveedor($d);
                }
            }

            $data2 = $this->db->select("SELECT coddivisa,SUM(total) as total" . $sql . " GROUP BY coddivisa");
            if ($data2) {
                $this->total_resultados_txt = 'Suma total de los resultados:';

                foreach ($data2 as $d) {
                    $this->total_resultados[] = array(
                        'coddivisa' => $d['coddivisa'],
                        'total' => floatval($d['total'])
                    );
                }
            }
        }
    }

    public function orden()
    {
        return array(
            'fecha_desc' => array(
                'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
                'texto' => 'Fecha',
                'orden' => 'fecha DESC'
            ),
            'fecha_asc' => array(
                'icono' => '<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true"></span>',
                'texto' => 'Fecha',
                'orden' => 'fecha ASC'
            ),
            'codigo_desc' => array(
                'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
                'texto' => 'Código',
                'orden' => 'codigo DESC'
            ),
            'codigo_asc' => array(
                'icono' => '<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true"></span>',
                'texto' => 'Código',
                'orden' => 'codigo ASC'
            ),
            'total_desc' => array(
                'icono' => '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>',
                'texto' => 'Total',
                'orden' => 'total DESC'
            )
        );
    }
}
