<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez      neorazorx@gmail.com
 * Copyright (C) 2014-2017  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

class ventas_presupuesto extends fbase_controller
{

    public $agencia;
    public $agente;
    public $almacen;
    public $cliente;
    public $cliente_s;
    public $divisa;
    public $ejercicio;
    public $fabricante;
    public $familia;
    public $forma_pago;
    public $impuesto;
    public $historico;
    public $nuevo_presupuesto_url;
    public $pais;
    public $presupuesto;
    public $serie;
    public $setup_validez;
    public $versiones;

    public function __construct()
    {
        parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTO), 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        parent::private_core();

        $this->ppage = $this->page->get('ventas_presupuestos');
        $this->agencia = new agencia_transporte();
        $this->agente = FALSE;

        $presupuesto = new presupuesto_cliente();
        $this->presupuesto = FALSE;
        $this->almacen = new almacen();
        $this->cliente = new cliente();
        $this->cliente_s = FALSE;
        $this->divisa = new divisa();
        $this->ejercicio = new ejercicio();
        $this->fabricante = new fabricante();
        $this->familia = new familia();
        $this->forma_pago = new forma_pago();
        $this->impuesto = new impuesto();
        $this->nuevo_presupuesto_url = FALSE;
        $this->pais = new pais();
        $this->serie = new serie();
        $this->setup_validez = 30;
        $this->configurar_validez();

        /**
         * Comprobamos si el usuario tiene acceso a nueva_venta,
         * necesario para poder añadir líneas.
         */
        if ($this->user->have_access_to('nueva_venta', FALSE)) {
            $nuevoprep = $this->page->get('nueva_venta');
            if ($nuevoprep) {
                $this->nuevo_presupuesto_url = $nuevoprep->url();
            }
        }

        if (isset($_POST['idpresupuesto'])) {
            $this->presupuesto = $presupuesto->get($_POST['idpresupuesto']);
            $this->modificar();
        } else if (isset($_GET['id'])) {
            $this->presupuesto = $presupuesto->get($_GET['id']);
        }

        if ($this->presupuesto) {
            $this->page->title = $this->presupuesto->codigo;

            /// cargamos el agente
            if (!is_null($this->presupuesto->codagente)) {
                $agente = new agente();
                $this->agente = $agente->get($this->presupuesto->codagente);
            }

            /// cargamos el cliente
            $this->cliente_s = $this->cliente->get($this->presupuesto->codcliente);

            /// comprobamos el presupuesto
            if ($this->presupuesto->full_test()) {
                if (isset($_REQUEST['status'])) {
                    $this->presupuesto->status = intval($_REQUEST['status']);

                    /// ¿El presupuesto tiene una fecha de validez pasada y queremos ponerlo pendiente?
                    if ($this->presupuesto->finoferta() && intval($_REQUEST['status']) == 0) {
                        $this->presupuesto->finoferta = date('d-m-Y', strtotime('+' . $this->setup_validez . ' days'));
                    }

                    if ($this->presupuesto->status == 1 && is_null($this->presupuesto->idpedido)) {
                        $this->generar_pedido();
                    } else if ($this->presupuesto->save()) {
                        $this->new_message(ucfirst(FS_PRESUPUESTO) . " modificado correctamente.");
                    } else {
                        $this->new_error_msg("¡Imposible modificar el " . FS_PRESUPUESTO . "!");
                    }
                } else if (isset($_GET['nversion'])) {
                    $this->nueva_version();
                } else {
                    if (isset($_GET['nversionok'])) {
                        $this->new_message('Esta es la nueva versión del ' . FS_PRESUPUESTO . '.');
                    }

                    /// Comprobamos las líneas
                    $this->check_lineas();
                }
            }

            $this->versiones = $this->presupuesto->get_versiones();
            $this->get_historico();
        } else {
            $this->new_error_msg("¡" . ucfirst(FS_PRESUPUESTO) . " de cliente no encontrado!", 'error', FALSE, FALSE);
        }
    }

    private function nueva_version()
    {
        $presu = clone $this->presupuesto;
        $presu->idpresupuesto = NULL;
        $presu->idpedido = NULL;
        $presu->fecha = $this->today();
        $presu->hora = $this->hour();
        $presu->finoferta = date('d-m-Y', strtotime($presu->fecha . ' +' . $this->setup_validez . ' days'));
        $presu->femail = NULL;
        $presu->status = 0;
        $presu->numdocs = 0;

        $presu->idoriginal = $this->presupuesto->idpresupuesto;
        if ($this->presupuesto->idoriginal) {
            $presu->idoriginal = $this->presupuesto->idoriginal;
        }

        /// enlazamos con el ejercicio correcto
        $ejercicio = $this->ejercicio->get_by_fecha($presu->fecha);
        if ($ejercicio) {
            $presu->codejercicio = $ejercicio->codejercicio;
        }

        if ($presu->save()) {
            /// también copiamos las líneas del presupuesto
            foreach ($this->presupuesto->get_lineas() as $linea) {
                $newl = clone $linea;
                $newl->idlinea = NULL;
                $newl->idpresupuesto = $presu->idpresupuesto;
                $newl->save();
            }

            $this->new_message('<a href="' . $presu->url() . '">Documento</a> de ' . FS_PRESUPUESTO . ' copiado correctamente.');
            header('Location: ' . $presu->url() . '&nversionok=TRUE');
        } else {
            $this->new_error_msg('Error al copiar el documento.');
        }
    }

    /**
     * Comprobamos si los artículos han variado su precio.
     * @return type
     */
    private function check_lineas()
    {
        if ($this->presupuesto->status == 0 && $this->presupuesto->coddivisa == $this->empresa->coddivisa) {
            foreach ($this->presupuesto->get_lineas() as $l) {
                if ($l->referencia != '') {
                    $data = $this->db->select("SELECT factualizado,pvp FROM articulos WHERE referencia = " . $l->var2str($l->referencia) . " ORDER BY referencia ASC;");
                    if ($data && strtotime($data[0]["factualizado"]) > strtotime($this->presupuesto->fecha)) {
                        if ($l->pvpunitario > floatval($data[0]['pvp'])) {
                            $this->new_advice("El precio del artículo <a href='" . $l->articulo_url() . "'>" . $l->referencia . "</a>"
                                . " ha bajado desde la elaboración del " . FS_PRESUPUESTO . ".");
                        } else if ($l->pvpunitario < floatval($data[0]['pvp'])) {
                            $this->new_advice("El precio del artículo <a href='" . $l->articulo_url() . "'>" . $l->referencia . "</a>"
                                . " ha subido desde la elaboración del " . FS_PRESUPUESTO . ".");
                        }
                    }
                }
            }
        }
    }

    public function url()
    {
        if (!isset($this->presupuesto)) {
            return parent::url();
        } else if ($this->presupuesto) {
            return $this->presupuesto->url();
        }

        return $this->page->url();
    }

    private function modificar()
    {
        $this->presupuesto->observaciones = $_POST['observaciones'];

        /// ¿El presupuesto es editable o ya ha sido aprobado?
        if ($this->presupuesto->editable) {
            $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha'], FALSE);
            if ($eje0) {
                $this->presupuesto->fecha = $_POST['fecha'];
                $this->presupuesto->hora = $_POST['hora'];

                $this->presupuesto->finoferta = NULL;
                if ($_POST['finoferta'] != '') {
                    $this->presupuesto->finoferta = $_POST['finoferta'];
                }

                if ($this->presupuesto->codejercicio != $eje0->codejercicio) {
                    $this->presupuesto->codejercicio = $eje0->codejercicio;
                    $this->presupuesto->new_codigo();
                }
            } else {
                $this->new_error_msg('Ningún ejercicio encontrado.');
            }

            /// ¿cambiamos el cliente?
            if ($_POST['cliente'] != $this->presupuesto->codcliente) {
                $cliente = $this->cliente->get($_POST['cliente']);
                if ($cliente) {
                    $this->presupuesto->codcliente = $cliente->codcliente;
                    $this->presupuesto->cifnif = $cliente->cifnif;
                    $this->presupuesto->nombrecliente = $cliente->razonsocial;
                    $this->presupuesto->apartado = NULL;
                    $this->presupuesto->ciudad = NULL;
                    $this->presupuesto->coddir = NULL;
                    $this->presupuesto->codpais = NULL;
                    $this->presupuesto->codpostal = NULL;
                    $this->presupuesto->direccion = NULL;
                    $this->presupuesto->provincia = NULL;

                    foreach ($cliente->get_direcciones() as $d) {
                        if ($d->domfacturacion) {
                            $this->presupuesto->apartado = $d->apartado;
                            $this->presupuesto->ciudad = $d->ciudad;
                            $this->presupuesto->coddir = $d->id;
                            $this->presupuesto->codpais = $d->codpais;
                            $this->presupuesto->codpostal = $d->codpostal;
                            $this->presupuesto->direccion = $d->direccion;
                            $this->presupuesto->provincia = $d->provincia;
                            break;
                        }
                    }
                } else {
                    $this->presupuesto->codcliente = NULL;
                    $this->presupuesto->nombrecliente = $_POST['nombrecliente'];
                    $this->presupuesto->cifnif = $_POST['cifnif'];
                    $this->presupuesto->coddir = NULL;
                }
            } else {
                $this->presupuesto->nombrecliente = $_POST['nombrecliente'];
                $this->presupuesto->cifnif = $_POST['cifnif'];
                $this->presupuesto->codpais = $_POST['codpais'];
                $this->presupuesto->provincia = $_POST['provincia'];
                $this->presupuesto->ciudad = $_POST['ciudad'];
                $this->presupuesto->codpostal = $_POST['codpostal'];
                $this->presupuesto->direccion = $_POST['direccion'];
                $this->presupuesto->apartado = $_POST['apartado'];

                /// envío
                $this->presupuesto->envio_nombre = $_POST['envio_nombre'];
                $this->presupuesto->envio_apellidos = $_POST['envio_apellidos'];
                $this->presupuesto->envio_codtrans = NULL;
                if ($_POST['envio_codtrans'] != '') {
                    $this->presupuesto->envio_codtrans = $_POST['envio_codtrans'];
                }
                $this->presupuesto->envio_codigo = $_POST['envio_codigo'];
                $this->presupuesto->envio_codpais = $_POST['envio_codpais'];
                $this->presupuesto->envio_provincia = $_POST['envio_provincia'];
                $this->presupuesto->envio_ciudad = $_POST['envio_ciudad'];
                $this->presupuesto->envio_codpostal = $_POST['envio_codpostal'];
                $this->presupuesto->envio_direccion = $_POST['envio_direccion'];
                $this->presupuesto->envio_apartado = $_POST['envio_apartado'];

                $cliente = $this->cliente->get($this->presupuesto->codcliente);
            }

            $serie = $this->serie->get($this->presupuesto->codserie);

            /// ¿cambiamos la serie?
            if ($_POST['serie'] != $this->presupuesto->codserie) {
                $serie2 = $this->serie->get($_POST['serie']);
                if ($serie2) {
                    $this->presupuesto->codserie = $serie2->codserie;
                    $this->presupuesto->new_codigo();

                    $serie = $serie2;
                }
            }

            $this->presupuesto->codalmacen = $_POST['almacen'];
            $this->presupuesto->codpago = $_POST['forma_pago'];

            /// ¿Cambiamos la divisa?
            if ($_POST['divisa'] != $this->presupuesto->coddivisa) {
                $divisa = $this->divisa->get($_POST['divisa']);
                if ($divisa) {
                    $this->presupuesto->coddivisa = $divisa->coddivisa;
                    $this->presupuesto->tasaconv = $divisa->tasaconv;
                }
            } else if ($_POST['tasaconv'] != '') {
                $this->presupuesto->tasaconv = floatval($_POST['tasaconv']);
            }

            if (isset($_POST['numlineas'])) {
                $numlineas = intval($_POST['numlineas']);

                $this->presupuesto->irpf = 0;
                $this->presupuesto->netosindto = 0;
                $this->presupuesto->neto = 0;
                $this->presupuesto->totaliva = 0;
                $this->presupuesto->totalirpf = 0;
                $this->presupuesto->totalrecargo = 0;
                $this->presupuesto->dtopor1 = floatval($_POST['adtopor1']);
                $this->presupuesto->dtopor2 = floatval($_POST['adtopor2']);
                $this->presupuesto->dtopor3 = floatval($_POST['adtopor3']);
                $this->presupuesto->dtopor4 = floatval($_POST['adtopor4']);
                $this->presupuesto->dtopor5 = floatval($_POST['adtopor5']);

                $lineas = $this->presupuesto->get_lineas();
                $articulo = new articulo();

                /// eliminamos las líneas que no encontremos en el $_POST
                foreach ($lineas as $l) {
                    $encontrada = FALSE;
                    for ($num = 0; $num <= $numlineas; $num++) {
                        if (isset($_POST['idlinea_' . $num]) && $l->idlinea == intval($_POST['idlinea_' . $num])) {
                            $encontrada = TRUE;
                            break;
                        }
                    }
                    if (!$encontrada && !$l->delete()) {
                        $this->new_error_msg("¡Imposible eliminar la línea del artículo " . $l->referencia . "!");
                    }
                }

                $regimeniva = 'general';
                if ($cliente) {
                    $regimeniva = $cliente->regimeniva;
                }

                /// modificamos y/o añadimos las demás líneas
                for ($num = 0; $num <= $numlineas; $num++) {
                    $encontrada = FALSE;
                    if (isset($_POST['idlinea_' . $num])) {
                        foreach ($lineas as $k => $value) {
                            /// modificamos la línea
                            if ($value->idlinea == intval($_POST['idlinea_' . $num])) {
                                $encontrada = TRUE;
                                $lineas[$k]->cantidad = floatval($_POST['cantidad_' . $num]);
                                $lineas[$k]->pvpunitario = floatval($_POST['pvp_' . $num]);
                                $lineas[$k]->dtopor = floatval(fs_filter_input_post('dto_' . $num, 0));
                                $lineas[$k]->dtopor2 = floatval(fs_filter_input_post('dto2_' . $num, 0));
                                $lineas[$k]->dtopor3 = floatval(fs_filter_input_post('dto3_' . $num, 0));
                                $lineas[$k]->dtopor4 = floatval(fs_filter_input_post('dto4_' . $num, 0));
                                $lineas[$k]->pvpsindto = $value->cantidad * $value->pvpunitario;

                                // Descuento Unificado Equivalente
                                $due_linea = $this->fbase_calc_due(array($lineas[$k]->dtopor, $lineas[$k]->dtopor2, $lineas[$k]->dtopor3, $lineas[$k]->dtopor4));
                                $lineas[$k]->pvptotal = $lineas[$k]->cantidad * $lineas[$k]->pvpunitario * $due_linea;

                                $lineas[$k]->descripcion = $_POST['desc_' . $num];
                                $lineas[$k]->codimpuesto = NULL;
                                $lineas[$k]->iva = 0;
                                $lineas[$k]->recargo = 0;
                                $lineas[$k]->irpf = floatval(fs_filter_input_post('irpf_' . $num, 0));
                                if (!$serie->siniva && $regimeniva != 'Exento') {
                                    $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                                    if ($imp0) {
                                        $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                                    }

                                    $lineas[$k]->iva = floatval($_POST['iva_' . $num]);
                                    $lineas[$k]->recargo = floatval(fs_filter_input_post('recargo_' . $num, 0));
                                }

                                if ($lineas[$k]->save()) {
                                    if ($value->irpf > $this->presupuesto->irpf) {
                                        $this->presupuesto->irpf = $value->irpf;
                                    }
                                } else {
                                    $this->new_error_msg("¡Imposible modificar la línea del artículo " . $value->referencia . "!");
                                }

                                break;
                            }
                        }

                        /// añadimos la línea
                        if (!$encontrada && intval($_POST['idlinea_' . $num]) == -1 && isset($_POST['referencia_' . $num])) {
                            $linea = new linea_presupuesto_cliente();
                            $linea->idpresupuesto = $this->presupuesto->idpresupuesto;
                            $linea->descripcion = $_POST['desc_' . $num];

                            if (!$serie->siniva && $regimeniva != 'Exento') {
                                $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                                if ($imp0) {
                                    $linea->codimpuesto = $imp0->codimpuesto;
                                }

                                $linea->iva = floatval($_POST['iva_' . $num]);
                                $linea->recargo = floatval(fs_filter_input_post('recargo_' . $num, 0));
                            }

                            $linea->irpf = floatval(fs_filter_input_post('irpf_' . $num, 0));
                            $linea->cantidad = floatval($_POST['cantidad_' . $num]);
                            $linea->pvpunitario = floatval($_POST['pvp_' . $num]);
                            $linea->dtopor = floatval(fs_filter_input_post('dto_' . $num, 0));
                            $linea->dtopor2 = floatval(fs_filter_input_post('dto2_' . $num, 0));
                            $linea->dtopor3 = floatval(fs_filter_input_post('dto3_' . $num, 0));
                            $linea->dtopor4 = floatval(fs_filter_input_post('dto4_' . $num, 0));
                            $linea->pvpsindto = $linea->cantidad * $linea->pvpunitario;

                            // Descuento Unificado Equivalente
                            $due_linea = $this->fbase_calc_due(array($linea->dtopor, $linea->dtopor2, $linea->dtopor3, $linea->dtopor4));
                            $linea->pvptotal = $linea->cantidad * $linea->pvpunitario * $due_linea;

                            $art0 = $articulo->get($_POST['referencia_' . $num]);
                            if ($art0) {
                                $linea->referencia = $art0->referencia;
                                if ($_POST['codcombinacion_' . $num]) {
                                    $linea->codcombinacion = $_POST['codcombinacion_' . $num];
                                }
                            }

                            if ($linea->save()) {
                                if ($linea->irpf > $this->presupuesto->irpf) {
                                    $this->presupuesto->irpf = $linea->irpf;
                                }
                            } else {
                                $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "!");
                            }
                        }
                    }
                }

                /// obtenemos los subtotales por impuesto
                $due_totales = $this->fbase_calc_due([$this->presupuesto->dtopor1, $this->presupuesto->dtopor2, $this->presupuesto->dtopor3, $this->presupuesto->dtopor4, $this->presupuesto->dtopor5]);
                foreach ($this->fbase_get_subtotales_documento($this->presupuesto->get_lineas(), $due_totales) as $subt) {
                    $this->presupuesto->netosindto += $subt['netosindto'];
                    $this->presupuesto->neto += $subt['neto'];
                    $this->presupuesto->totaliva += $subt['iva'];
                    $this->presupuesto->totalirpf += $subt['irpf'];
                    $this->presupuesto->totalrecargo += $subt['recargo'];
                }

                $this->presupuesto->total = round($this->presupuesto->neto + $this->presupuesto->totaliva - $this->presupuesto->totalirpf + $this->presupuesto->totalrecargo, FS_NF0);

                if (abs(floatval($_POST['atotal']) - $this->presupuesto->total) >= .02) {
                    $this->new_error_msg("El total difiere entre el controlador y la vista (" . $this->presupuesto->total .
                        " frente a " . $_POST['atotal'] . "). Debes informar del error.");
                }
            }
        }

        if (!fs_generar_numero2($this->presupuesto)) {
            $this->presupuesto->numero2 = $_POST['numero2'];
        }

        if ($this->presupuesto->save()) {
            $this->new_message(ucfirst(FS_PRESUPUESTO) . " modificado correctamente.");
            $this->new_change(ucfirst(FS_PRESUPUESTO) . ' Cliente ' . $this->presupuesto->codigo, $this->presupuesto->url());
        } else {
            $this->new_error_msg("¡Imposible modificar el " . FS_PRESUPUESTO . "!");
        }
    }

    private function generar_pedido()
    {
        $pedido = new pedido_cliente();
        $pedido->apartado = $this->presupuesto->apartado;
        $pedido->cifnif = $this->presupuesto->cifnif;
        $pedido->ciudad = $this->presupuesto->ciudad;
        $pedido->codagente = $this->presupuesto->codagente;
        $pedido->codalmacen = $this->presupuesto->codalmacen;
        $pedido->codcliente = $this->presupuesto->codcliente;
        $pedido->coddir = $this->presupuesto->coddir;
        $pedido->coddivisa = $this->presupuesto->coddivisa;
        $pedido->tasaconv = $this->presupuesto->tasaconv;
        $pedido->codpago = $this->presupuesto->codpago;
        $pedido->codpais = $this->presupuesto->codpais;
        $pedido->codpostal = $this->presupuesto->codpostal;
        $pedido->codserie = $this->presupuesto->codserie;
        $pedido->direccion = $this->presupuesto->direccion;
        $pedido->netosindto = $this->presupuesto->netosindto;
        $pedido->dtopor1 = $this->presupuesto->dtopor1;
        $pedido->dtopor2 = $this->presupuesto->dtopor2;
        $pedido->dtopor3 = $this->presupuesto->dtopor3;
        $pedido->dtopor4 = $this->presupuesto->dtopor4;
        $pedido->dtopor5 = $this->presupuesto->dtopor5;
        $pedido->neto = $this->presupuesto->neto;
        $pedido->nombrecliente = $this->presupuesto->nombrecliente;
        $pedido->observaciones = $this->presupuesto->observaciones;
        $pedido->provincia = $this->presupuesto->provincia;
        $pedido->total = $this->presupuesto->total;
        $pedido->totaliva = $this->presupuesto->totaliva;
        $pedido->irpf = $this->presupuesto->irpf;
        $pedido->porcomision = $this->presupuesto->porcomision;
        $pedido->totalirpf = $this->presupuesto->totalirpf;
        $pedido->totalrecargo = $this->presupuesto->totalrecargo;

        $pedido->envio_nombre = $this->presupuesto->envio_nombre;
        $pedido->envio_apellidos = $this->presupuesto->envio_apellidos;
        $pedido->envio_codtrans = $this->presupuesto->envio_codtrans;
        $pedido->envio_codigo = $this->presupuesto->envio_codigo;
        $pedido->envio_codpais = $this->presupuesto->envio_codpais;
        $pedido->envio_provincia = $this->presupuesto->envio_provincia;
        $pedido->envio_ciudad = $this->presupuesto->envio_ciudad;
        $pedido->envio_codpostal = $this->presupuesto->envio_codpostal;
        $pedido->envio_direccion = $this->presupuesto->envio_direccion;
        $pedido->envio_apartado = $this->presupuesto->envio_apartado;

        if (is_null($pedido->codagente)) {
            $pedido->codagente = $this->user->codagente;
        }

        /**
         * Obtenemos el ejercicio para la fecha de hoy (puede que no sea
         * el mismo ejercicio que el del presupuesto, por ejemplo si hemos cambiado de año).
         */
        $eje0 = $this->ejercicio->get_by_fecha($pedido->fecha, FALSE);
        if ($eje0) {
            $pedido->codejercicio = $eje0->codejercicio;
        }

        if (!fs_generar_numero2($pedido)) {
            $pedido->numero2 = $this->presupuesto->numero2;
        }

        if (!$eje0) {
            $this->new_error_msg("Ejercicio no encontrado.");
        } else if (!$eje0->abierto()) {
            $this->new_error_msg("El ejercicio está cerrado.");
        } else if ($pedido->save()) {
            $continuar = TRUE;
            foreach ($this->presupuesto->get_lineas() as $l) {
                $n = new linea_pedido_cliente();
                $n->idlineapresupuesto = $l->idlinea;
                $n->idpresupuesto = $l->idpresupuesto;
                $n->idpedido = $pedido->idpedido;
                $n->cantidad = $l->cantidad;
                $n->codimpuesto = $l->codimpuesto;
                $n->descripcion = $l->descripcion;
                $n->dtopor = $l->dtopor;
                $n->dtopor2 = $l->dtopor2;
                $n->dtopor3 = $l->dtopor3;
                $n->dtopor4 = $l->dtopor4;
                $n->irpf = $l->irpf;
                $n->iva = $l->iva;
                $n->pvpsindto = $l->pvpsindto;
                $n->pvptotal = $l->pvptotal;
                $n->pvpunitario = $l->pvpunitario;
                $n->recargo = $l->recargo;
                $n->referencia = $l->referencia;
                $n->codcombinacion = $l->codcombinacion;
                $n->orden = $l->orden;
                $n->mostrar_cantidad = $l->mostrar_cantidad;
                $n->mostrar_precio = $l->mostrar_precio;

                if (!$n->save()) {
                    $continuar = FALSE;
                    $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
                    break;
                }
            }

            if ($continuar) {
                $this->presupuesto->idpedido = $pedido->idpedido;

                if ($this->presupuesto->save()) {
                    $this->new_message("<a href='" . $pedido->url() . "'>" . ucfirst(FS_PEDIDO) . '</a> generado correctamente.');
                } else {
                    $this->new_error_msg("¡Imposible vincular el " . FS_PRESUPUESTO . " con el nuevo " . FS_PEDIDO . "!");
                    if ($pedido->delete()) {
                        $this->new_error_msg("El " . FS_PEDIDO . " se ha borrado.");
                    } else {
                        $this->new_error_msg("¡Imposible borrar el " . FS_PEDIDO . "!");
                    }
                }
            } else if ($pedido->delete()) {
                $this->new_error_msg("El " . FS_PEDIDO . " se ha borrado.");
            } else {
                $this->new_error_msg("¡Imposible borrar el " . FS_PEDIDO . "!");
            }
        } else {
            $this->new_error_msg("¡Imposible guardar el " . FS_PEDIDO . "!");
        }
    }

    private function configurar_validez()
    {
        $fsvar = new fs_var();
        if (isset($_POST['setup_validez'])) {
            $this->setup_validez = intval($_POST['setup_validez']);
            $fsvar->simple_save('presu_validez', $this->setup_validez);
            $this->new_message('Configuración modificada correctamente.');
        } else {
            $dias = $fsvar->simple_get('presu_validez');
            if ($dias) {
                $this->setup_validez = intval($dias);
            }
        }
    }

    public function get_lineas_stock()
    {
        $lineas = array();

        $sql = "SELECT l.referencia,l.descripcion,l.cantidad,s.cantidad as stock,s.ubicacion FROM lineaspresupuestoscli l"
            . " LEFT JOIN stocks s ON l.referencia = s.referencia"
            . " AND s.codalmacen = " . $this->presupuesto->var2str($this->presupuesto->codalmacen)
            . " WHERE l.idpresupuesto = " . $this->presupuesto->var2str($this->presupuesto->idpresupuesto)
            . " ORDER BY referencia ASC;";
        $data = $this->db->select($sql);
        if ($data) {
            $art0 = new articulo();

            foreach ($data as $d) {
                $articulo = $art0->get($d['referencia']);
                if ($articulo) {
                    $lineas[] = array(
                        'articulo' => $articulo,
                        'descripcion' => $d['descripcion'],
                        'cantidad' => floatval($d['cantidad']),
                        'stock' => floatval($d['stock']),
                        'ubicacion' => $d['ubicacion']
                    );
                }
            }
        }

        return $lineas;
    }

    private function get_historico()
    {
        $this->historico = array();
        $orden = 0;

        if ($this->presupuesto->idpedido) {
            /// pedido
            $sql = "SELECT * FROM pedidoscli WHERE idpedido = " . $this->presupuesto->var2str($this->presupuesto->idpedido)
                . " ORDER BY idpedido ASC;";

            $data = $this->db->select($sql);
            if ($data) {
                foreach ($data as $d) {
                    $pedido = new pedido_cliente($d);
                    $this->historico[] = array(
                        'orden' => $orden,
                        'documento' => FS_PEDIDO,
                        'modelo' => $pedido
                    );
                    $orden++;

                    if ($pedido->idalbaran) {
                        /// albarán
                        $sql1 = "SELECT * FROM albaranescli WHERE idalbaran = " . $pedido->var2str($pedido->idalbaran)
                            . " ORDER BY idalbaran ASC;";

                        $data1 = $this->db->select($sql1);
                        if ($data1) {
                            foreach ($data1 as $d1) {
                                $albaran = new albaran_cliente($d1);
                                $this->historico[] = array(
                                    'orden' => $orden,
                                    'documento' => FS_ALBARAN,
                                    'modelo' => $albaran
                                );
                                $orden++;

                                if ($albaran->idfactura) {
                                    /// factura
                                    $sql2 = "SELECT * FROM facturascli WHERE idfactura = " . $albaran->var2str($albaran->idfactura)
                                        . " ORDER BY idfactura ASC;";

                                    $data2 = $this->db->select($sql2);
                                    if ($data2) {
                                        foreach ($data2 as $d2) {
                                            $factura = new factura_cliente($d2);
                                            $this->historico[] = array(
                                                'orden' => $orden,
                                                'documento' => 'factura',
                                                'modelo' => $factura
                                            );
                                            $orden++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
