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
namespace FacturaScripts\model;

/**
 * Presupuesto de cliente
 */
class presupuesto_cliente extends \fs_model
{

    /**
     * Clave primaria.
     * @var integer 
     */
    public $idpresupuesto;

    /**
     * ID del pedido relacionado, si lo hay.
     * @var integer 
     */
    public $idpedido;

    /**
     * Código identificador único. Para humanos.
     * @var string
     */
    public $codigo;

    /**
     * Serie relacionada.
     * @var string
     */
    public $codserie;

    /**
     * Ejercicio relacionado. El que corresponde a la fecha.
     * @var string
     */
    public $codejercicio;

    /**
     * Código del cliente del presupuesto.
     * @var string
     */
    public $codcliente;

    /**
     * Empleado que ha creado el presupuesto.
     * @var string
     */
    public $codagente;

    /**
     * Forma de pago del presupuesto.
     * @var string
     */
    public $codpago;

    /**
     * Divisa del presupuesto.
     * @var string
     */
    public $coddivisa;

    /**
     * Almacén del que saldría la mercancía.
     * @var string
     */
    public $codalmacen;

    /**
     * País del cliente.
     * @var string
     */
    public $codpais;

    /**
     * ID de la dirección del cliente.
     * Modelo direccion_cliente.
     * @var string
     */
    public $coddir;

    /**
     * Código postal del cliente.
     * @var string
     */
    public $codpostal;

    /**
     * Número de presupuesto.
     * Único dentro de la serie+ejercicio.
     * @var string
     */
    public $numero;

    /**
     * Número opcional a disposición del usuario.
     * @var string
     */
    public $numero2;

    /**
     * TODO
     * @var
     */
    public $nombrecliente;

    /**
     * TODO
     * @var
     */
    public $cifnif;

    /**
     * TODO
     * @var
     */
    public $direccion;

    /**
     * TODO
     * @var
     */
    public $ciudad;

    /**
     * TODO
     * @var
     */
    public $provincia;

    /**
     * TODO
     * @var
     */
    public $apartado;

    /**
     * TODO
     * @var
     */
    public $fecha;

    /**
     * TODO
     * @var
     */
    public $hora;

    /**
     * Fecha en la que termina la validéz del presupuesto.
     * @var float
     */
    public $finoferta;

    /**
     * Importe total antes de descuentos e impuestos.
     * Es la suma del pvptotal de las líneas.
     * @var float
     */
    public $netosindto;

    /**
     * Importe total antes de impuestos.
     * Es la suma del pvptotal de las líneas.
     * @var float
     */
    public $neto;

    /**
     * Descuento porcentual 1
     * @var float
     */
    public $dtopor1;
    
    /**
     * Descuento porcentual 2
     * @var float
     */
    public $dtopor2;
    
    /**
     * Descuento porcentual 3
     * @var float
     */
    public $dtopor3;
    
    /**
     * Descuento porcentual 4
     * @var float
     */
    public $dtopor4;
    
    /**
     * Descuento porcentual 5
     * @var float
     */
    public $dtopor5;

    /**
     * Importe total del presupuesto, con impuestos.
     * @var float
     */
    public $total;

    /**
     * Suma del IVA de las líneas.
     * @var float
     */
    public $totaliva;

    /**
     * Total expresado en euros, por si no fuese la divisa del presupuesto.
     * totaleuros = total/tasaconv
     * No hace falta rellenarlo, al hacer save() se calcula el valor.
     * @var float
     */
    public $totaleuros;

    /**
     * % de retención IRPF del presupuesto. Se obtiene de la serie.
     * Cada línea puede tener un % distinto.
     * @var float
     */
    public $irpf;

    /**
     * Suma total de las retenciones IRPF de las líneas.
     * @var float
     */
    public $totalirpf;

    /**
     * % de comisión del empleado (agente).
     * @var float
     */
    public $porcomision;

    /**
     * Tasa de conversión a Euros de la divisa seleccionada.
     * @var float
     */
    public $tasaconv;

    /**
     * Suma total del recargo de equivalencia de las líneas.
     * @var float
     */
    public $totalrecargo;

    /**
     * Observaciones del pedido
     * @var float
     */
    public $observaciones;

    /**
     * Estado del presupuesto:
     * 0 -> pendiente. (editable)
     * 1 -> aprobado. (hay un idpedido y no es editable)
     * 2 -> rechazado. (no hay idpedido y no es editable)
     * @var integer
     */
    public $status;
    public $editable;

    /**
     * Fecha en la que se envió el presupuesto por email.
     * @var string 
     */
    public $femail;

    /// datos de transporte

    /**
     * TODO
     * @var
     */
    public $envio_codtrans;

    /**
     * TODO
     * @var
     */
    public $envio_codigo;

    /**
     * TODO
     * @var
     */
    public $envio_nombre;

    /**
     * TODO
     * @var
     */
    public $envio_apellidos;

    /**
     * TODO
     * @var
     */
    public $envio_apartado;

    /**
     * TODO
     * @var
     */
    public $envio_direccion;

    /**
     * TODO
     * @var
     */
    public $envio_codpostal;

    /**
     * TODO
     * @var
     */
    public $envio_ciudad;

    /**
     * TODO
     * @var
     */
    public $envio_provincia;

    /**
     * TODO
     * @var
     */
    public $envio_codpais;

    /**
     * Número de documentos adjuntos.
     * @var integer 
     */
    public $numdocs;

    /**
     * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
     * @var integer
     */
    public $idoriginal;

    public function __construct($p = FALSE)
    {
        parent::__construct('presupuestoscli');
        if ($p) {
            $this->idpresupuesto = $this->intval($p['idpresupuesto']);
            $this->idpedido = $this->intval($p['idpedido']);
            $this->codigo = $p['codigo'];
            $this->codagente = $p['codagente'];
            $this->codpago = $p['codpago'];
            $this->codserie = $p['codserie'];
            $this->codejercicio = $p['codejercicio'];
            $this->codcliente = $p['codcliente'];
            $this->coddivisa = $p['coddivisa'];
            $this->codalmacen = $p['codalmacen'];
            $this->codpais = $p['codpais'];
            $this->coddir = $p['coddir'];
            $this->codpostal = $p['codpostal'];
            $this->numero = $p['numero'];
            $this->numero2 = $p['numero2'];
            $this->nombrecliente = $p['nombrecliente'];
            $this->cifnif = $p['cifnif'];
            $this->direccion = $p['direccion'];
            $this->ciudad = $p['ciudad'];
            $this->provincia = $p['provincia'];
            $this->apartado = $p['apartado'];
            $this->fecha = Date('d-m-Y', strtotime($p['fecha']));

            $this->finoferta = NULL;
            if (!is_null($p['finoferta'])) {
                $this->finoferta = Date('d-m-Y', strtotime($p['finoferta']));
            }

            $this->hora = '00:00:00';
            if (!is_null($p['hora'])) {
                $this->hora = date('H:i:s', strtotime($p['hora']));
            }

            $this->netosindto = isset($p['netosindto']) ? floatval($p['netosindto']) : 0;
            $this->neto = floatval($p['neto']);
            $this->dtopor1 = isset($p['dtopor1']) ? floatval($p['dtopor1']) : 0;
            $this->dtopor2 = isset($p['dtopor2']) ? floatval($p['dtopor2']) : 0;
            $this->dtopor3 = isset($p['dtopor3']) ? floatval($p['dtopor3']) : 0;
            $this->dtopor4 = isset($p['dtopor4']) ? floatval($p['dtopor4']) : 0;
            $this->dtopor5 = isset($p['dtopor5']) ? floatval($p['dtopor5']) : 0;
            $this->total = floatval($p['total']);
            $this->totaliva = floatval($p['totaliva']);
            $this->totaleuros = floatval($p['totaleuros']);
            $this->irpf = floatval($p['irpf']);
            $this->totalirpf = floatval($p['totalirpf']);
            $this->porcomision = floatval($p['porcomision']);
            $this->tasaconv = floatval($p['tasaconv']);
            $this->totalrecargo = floatval($p['totalrecargo']);
            $this->observaciones = $p['observaciones'];

            /// calculamos el estado para mantener compatibilidad con eneboo
            $this->status = intval($p['status']);
            $this->editable = $this->str2bool($p['editable']);
            if ($this->idpedido) {
                $this->status = 1;
                $this->editable = FALSE;
            } else if ($this->status == 2) {
                /// cancelado
                $this->editable = FALSE;
            } else if ($this->editable) {
                $this->status = 0;
            } else {
                $this->status = 2;
            }

            $this->femail = NULL;
            if (!is_null($p['femail'])) {
                $this->femail = Date('d-m-Y', strtotime($p['femail']));
            }

            $this->envio_codtrans = $p['codtrans'];
            $this->envio_codigo = $p['codigoenv'];
            $this->envio_nombre = $p['nombreenv'];
            $this->envio_apellidos = $p['apellidosenv'];
            $this->envio_apartado = $p['apartadoenv'];
            $this->envio_direccion = $p['direccionenv'];
            $this->envio_codpostal = $p['codpostalenv'];
            $this->envio_ciudad = $p['ciudadenv'];
            $this->envio_provincia = $p['provinciaenv'];
            $this->envio_codpais = $p['codpaisenv'];

            $this->numdocs = intval($p['numdocs']);
            $this->idoriginal = $this->intval($p['idoriginal']);
        } else {
            $this->idpresupuesto = NULL;
            $this->idpedido = NULL;
            $this->codigo = NULL;
            $this->codagente = NULL;
            $this->codpago = $this->default_items->codpago();
            $this->codserie = $this->default_items->codserie();
            $this->codejercicio = NULL;
            $this->codcliente = NULL;
            $this->coddivisa = NULL;
            $this->codalmacen = $this->default_items->codalmacen();
            $this->codpais = NULL;
            $this->coddir = NULL;
            $this->codpostal = '';
            $this->numero = NULL;
            $this->numero2 = NULL;
            $this->nombrecliente = '';
            $this->cifnif = '';
            $this->direccion = NULL;
            $this->ciudad = NULL;
            $this->provincia = NULL;
            $this->apartado = NULL;
            $this->fecha = Date('d-m-Y');
            $this->finoferta = date("d-m-Y", strtotime(Date('d-m-Y') . " +1month"));
            $this->hora = Date('H:i:s');
            $this->netosindto = 0.0;
            $this->neto = 0.0;
            $this->dtopor1 = 0.0;
            $this->dtopor2 = 0.0;
            $this->dtopor3 = 0.0;
            $this->dtopor4 = 0.0;
            $this->dtopor5 = 0.0;
            $this->total = 0.0;
            $this->totaliva = 0.0;
            $this->totaleuros = 0.0;
            $this->irpf = 0.0;
            $this->totalirpf = 0.0;
            $this->porcomision = 0.0;
            $this->tasaconv = 1.0;
            $this->totalrecargo = 0.0;
            $this->observaciones = NULL;
            $this->status = 0;
            $this->editable = TRUE;
            $this->femail = NULL;

            $this->envio_codtrans = NULL;
            $this->envio_codigo = NULL;
            $this->envio_nombre = NULL;
            $this->envio_apellidos = NULL;
            $this->envio_apartado = NULL;
            $this->envio_direccion = NULL;
            $this->envio_codpostal = NULL;
            $this->envio_ciudad = NULL;
            $this->envio_provincia = NULL;
            $this->envio_codpais = NULL;

            $this->numdocs = 0;
            $this->idoriginal = NULL;
        }
    }

    protected function install()
    {
        return '';
    }

    public function show_hora($s = TRUE)
    {
        if ($s) {
            return Date('H:i:s', strtotime($this->hora));
        }

        return Date('H:i', strtotime($this->hora));
    }

    public function observaciones_resume()
    {
        if ($this->observaciones == '') {
            return '-';
        } else if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }

        return substr($this->observaciones, 0, 50) . '...';
    }

    public function finoferta()
    {
        return ( strtotime(Date('d-m-Y')) > strtotime($this->finoferta) );
    }

    public function url()
    {
        if (is_null($this->idpresupuesto)) {
            return 'index.php?page=ventas_presupuestos';
        }

        return 'index.php?page=ventas_presupuesto&id=' . $this->idpresupuesto;
    }

    public function pedido_url()
    {
        if (is_null($this->idpedido)) {
            return 'index.php?page=ventas_pedido';
        }

        return 'index.php?page=ventas_pedido&id=' . $this->idpedido;
    }

    public function agente_url()
    {
        if (is_null($this->codagente)) {
            return "index.php?page=admin_agentes";
        }

        return "index.php?page=admin_agente&cod=" . $this->codagente;
    }

    public function cliente_url()
    {
        if (is_null($this->codcliente)) {
            return "index.php?page=ventas_clientes";
        }

        return "index.php?page=ventas_cliente&cod=" . $this->codcliente;
    }

    /**
     * Devuelve las líneas del presupuesto.
     * @return \linea_presupuesto_cliente
     */
    public function get_lineas()
    {
        $linea = new \linea_presupuesto_cliente();
        return $linea->all_from_presupuesto($this->idpresupuesto);
    }

    public function get_versiones()
    {
        $versiones = array();

        $sql = "SELECT * FROM " . $this->table_name . " WHERE idoriginal = " . $this->var2str($this->idpresupuesto);
        if ($this->idoriginal) {
            $sql .= " OR idoriginal = " . $this->var2str($this->idoriginal);
            $sql .= " OR idpresupuesto = " . $this->var2str($this->idoriginal);
        }
        $sql .= "ORDER BY fecha DESC, hora DESC;";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $versiones[] = new \presupuesto_cliente($d);
            }
        }

        return $versiones;
    }

    public function get($id)
    {
        $presupuesto = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idpresupuesto = " . $this->var2str($id) . ";");
        if ($presupuesto) {
            return new \presupuesto_cliente($presupuesto[0]);
        }

        return FALSE;
    }

    public function exists()
    {
        if (is_null($this->idpresupuesto)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idpresupuesto = " . $this->var2str($this->idpresupuesto) . ";");
    }

    /**
     * Genera un nuevo código y número para este presupuesto
     */
    public function new_codigo()
    {
        $this->numero = fs_documento_new_numero($this->db, $this->table_name, $this->codejercicio, $this->codserie, 'npresupuestocli');

        /**
         * Para evitar confusiones, si se elige "factura proforma" o algo similar
         * como traducción de FS_PRESUPUESTO, mejor ponemos "PRO" como inicio de código.
         */
        $tipodoc = strtoupper(substr(FS_PRESUPUESTO, 0, 3));
        if ($tipodoc == 'FAC') {
            $tipodoc = 'PRO';
        }
        $this->codigo = fs_documento_new_codigo($tipodoc, $this->codejercicio, $this->codserie, $this->numero);
    }

    /**
     * Comprueba los datos del presupuesto, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function test()
    {
        $this->nombrecliente = $this->no_html($this->nombrecliente);
        if ($this->nombrecliente == '') {
            $this->nombrecliente = '-';
        }

        $this->direccion = $this->no_html($this->direccion);
        $this->ciudad = $this->no_html($this->ciudad);
        $this->provincia = $this->no_html($this->provincia);
        $this->envio_nombre = $this->no_html($this->envio_nombre);
        $this->envio_apellidos = $this->no_html($this->envio_apellidos);
        $this->envio_direccion = $this->no_html($this->envio_direccion);
        $this->envio_ciudad = $this->no_html($this->envio_ciudad);
        $this->envio_provincia = $this->no_html($this->envio_provincia);
        $this->numero2 = $this->no_html($this->numero2);
        $this->observaciones = $this->no_html($this->observaciones);

        /**
         * Usamos el euro como divisa puente a la hora de sumar, comparar
         * o convertir cantidades en varias divisas. Por este motivo necesimos
         * muchos decimales.
         */
        $this->totaleuros = round($this->total / $this->tasaconv, 5);

        /// comprobamos que editable se corresponda con el status
        if ($this->idpedido) {
            $this->status = 1;
            $this->editable = FALSE;
        } else if ($this->status == 0) {
            $this->editable = TRUE;
        } else if ($this->status == 2) {
            $this->editable = FALSE;
        }

        if ($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE)) {
            return TRUE;
        }

        $this->new_error_msg("Error grave: El total está mal calculado. ¡Informa del error!");
        return FALSE;
    }

    /**
     * Comprobaciones extra del presupuesto, devuelve TRUE si está todo correcto
     * @param boolean $duplicados
     * @return boolean
     */
    public function full_test($duplicados = TRUE)
    {
        $status = TRUE;

        /// comprobamos las líneas
        $netos = array();
        $netosindto = 0;
        $netocondto = 0;
        $subtotal = 0;
        $neto = 0;
        $iva = 0;
        $irpf = 0;
        $recargo = 0;
        $total = 0;
        
        // Descuento total adicional del total del documento
        $t_dto_due = (1-((1-$this->dtopor1/100)*(1-$this->dtopor2/100)*(1-$this->dtopor3/100)*(1-$this->dtopor4/100)*(1-$this->dtopor5/100)))*100;
        $due_totales = (1-$t_dto_due/100);

        foreach ($this->get_lineas() as $l) {
            if (!$l->test()) {
                $status = FALSE;
            }
            $codimpuesto = ($l->codimpuesto === null ) ? 0 : $l->codimpuesto;
            if (!array_key_exists($codimpuesto, $netos)) {
                $netos[$codimpuesto] = array(
                    'subtotal' => 0,    // Subtotal (Sumatorio de netos de línea)
                    'base' => 0,        // Base imponible
                    'iva' => 0,         // Total IVA
                    'irpf' => 0,        // Total IRPF
                    'recargo' => 0      // Total Recargo
                );
            }
            // Acumulamos por tipos de IVAs, que es el desglose de pie de página
            $netosindto = $l->pvptotal;                 // Precio neto por línea
            $netocondto = $netosindto * $due_totales;   // Precio neto - % desc sobre total
            $netos[$codimpuesto]['subtotal'] += $netosindto;
            $netos[$codimpuesto]['base'] += $netocondto;
            $netos[$codimpuesto]['iva'] += $netocondto * ($l->iva / 100);
            $netos[$codimpuesto]['irpf'] += $netocondto * ($this->irpf / 100);
            $netos[$codimpuesto]['recargo'] += $netocondto * ($l->recargo / 100);
        }

        foreach ($netos as $codimp => $ne) {
            $subtotal += $ne['subtotal'];               // Subtotal (Sumatorio de netos de línea)
            $neto += $ne['base'];                       // Sumatorio de bases imponibles
            $iva += $ne['iva'];                         // Sumatorio de IVAs
            $irpf += $ne['irpf'];                       // Sumatorio de IRPFs
            $recargo += $ne['recargo'];                 // Sumatorio de REs
            $total = $neto + $iva - $irpf + $recargo;   // Sumatorio del total
        }

        if (!$this->floatcmp($this->neto, $neto, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor neto de " . FS_PRESUPUESTO . " " . $this->codigo . " incorrecto. Valor correcto: " . $neto . " y tiene el valor " . $this->neto);
            $status = FALSE;
        } elseif (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totaliva de " . FS_PRESUPUESTO . " " . $this->codigo . " incorrecto. Valor correcto: " . $iva . " y tiene el valor " . $this->totaliva);
            $status = FALSE;
        } elseif (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalirpf de " . FS_PRESUPUESTO . " " . $this->codigo . " incorrecto. Valor correcto: " . $irpf . " y tiene el valor " . $this->totalirpf);
            $status = FALSE;
        } elseif (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor totalrecargo de " . FS_PRESUPUESTO . " " . $this->codigo . " incorrecto. Valor correcto: " . $recargo . " y tiene el valor " . $this->totalrecargo);
            $status = FALSE;
        } elseif (!$this->floatcmp($this->total, $total, FS_NF0, TRUE)) {
            $this->new_error_msg("Valor total de " . FS_PRESUPUESTO . " " . $this->codigo . " incorrecto. Valor correcto: " . $total . " y tiene el valor " . $this->total);
            $status = FALSE;
        }

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET apartado = " . $this->var2str($this->apartado)
                    . ", cifnif = " . $this->var2str($this->cifnif)
                    . ", ciudad = " . $this->var2str($this->ciudad)
                    . ", codagente = " . $this->var2str($this->codagente)
                    . ", codalmacen = " . $this->var2str($this->codalmacen)
                    . ", codcliente = " . $this->var2str($this->codcliente)
                    . ", coddir = " . $this->var2str($this->coddir)
                    . ", coddivisa = " . $this->var2str($this->coddivisa)
                    . ", codejercicio = " . $this->var2str($this->codejercicio)
                    . ", codigo = " . $this->var2str($this->codigo)
                    . ", codpago = " . $this->var2str($this->codpago)
                    . ", codpais = " . $this->var2str($this->codpais)
                    . ", codpostal = " . $this->var2str($this->codpostal)
                    . ", codserie = " . $this->var2str($this->codserie)
                    . ", direccion = " . $this->var2str($this->direccion)
                    . ", editable = " . $this->var2str($this->editable)
                    . ", fecha = " . $this->var2str($this->fecha)
                    . ", finoferta = " . $this->var2str($this->finoferta)
                    . ", hora = " . $this->var2str($this->hora)
                    . ", idpedido = " . $this->var2str($this->idpedido)
                    . ", irpf = " . $this->var2str($this->irpf)
                    . ", netosindto = " . $this->var2str($this->netosindto)
                    . ", neto = " . $this->var2str($this->neto)
                    . ", dtopor1 = " . $this->var2str($this->dtopor1)
                    . ", dtopor2 = " . $this->var2str($this->dtopor2)
                    . ", dtopor3 = " . $this->var2str($this->dtopor3)
                    . ", dtopor4 = " . $this->var2str($this->dtopor4)
                    . ", dtopor5 = " . $this->var2str($this->dtopor5)
                    . ", nombrecliente = " . $this->var2str($this->nombrecliente)
                    . ", numero = " . $this->var2str($this->numero)
                    . ", numero2 = " . $this->var2str($this->numero2)
                    . ", observaciones = " . $this->var2str($this->observaciones)
                    . ", status = " . $this->var2str($this->status)
                    . ", porcomision = " . $this->var2str($this->porcomision)
                    . ", provincia = " . $this->var2str($this->provincia)
                    . ", tasaconv = " . $this->var2str($this->tasaconv)
                    . ", total = " . $this->var2str($this->total)
                    . ", totaleuros = " . $this->var2str($this->totaleuros)
                    . ", totalirpf = " . $this->var2str($this->totalirpf)
                    . ", totaliva = " . $this->var2str($this->totaliva)
                    . ", totalrecargo = " . $this->var2str($this->totalrecargo)
                    . ", femail = " . $this->var2str($this->femail)
                    . ", codtrans = " . $this->var2str($this->envio_codtrans)
                    . ", codigoenv = " . $this->var2str($this->envio_codigo)
                    . ", nombreenv = " . $this->var2str($this->envio_nombre)
                    . ", apellidosenv = " . $this->var2str($this->envio_apellidos)
                    . ", apartadoenv = " . $this->var2str($this->envio_apartado)
                    . ", direccionenv = " . $this->var2str($this->envio_direccion)
                    . ", codpostalenv = " . $this->var2str($this->envio_codpostal)
                    . ", ciudadenv = " . $this->var2str($this->envio_ciudad)
                    . ", provinciaenv = " . $this->var2str($this->envio_provincia)
                    . ", codpaisenv = " . $this->var2str($this->envio_codpais)
                    . ", numdocs = " . $this->var2str($this->numdocs)
                    . ", idoriginal = " . $this->var2str($this->idoriginal)
                    . "  WHERE idpresupuesto = " . $this->var2str($this->idpresupuesto) . ";";

                return $this->db->exec($sql);
            }

            $this->new_codigo();
            $sql = "INSERT INTO " . $this->table_name . " (apartado,cifnif,ciudad,codagente,codalmacen,
               codcliente,coddir,coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,
               direccion,editable,fecha,finoferta,hora,idpedido,irpf,netosindto,neto,dtopor1,dtopor2,dtopor3,dtopor4,dtopor5,
               nombrecliente,numero,observaciones,status,porcomision,provincia,tasaconv,total,totaleuros,
               totalirpf,totaliva,totalrecargo,numero2,femail,codtrans,codigoenv,nombreenv,apellidosenv,apartadoenv,
               direccionenv,codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs,idoriginal) VALUES ("
                . $this->var2str($this->apartado) . ","
                . $this->var2str($this->cifnif) . ","
                . $this->var2str($this->ciudad) . ","
                . $this->var2str($this->codagente) . ","
                . $this->var2str($this->codalmacen) . ","
                . $this->var2str($this->codcliente) . ","
                . $this->var2str($this->coddir) . ","
                . $this->var2str($this->coddivisa) . ","
                . $this->var2str($this->codejercicio) . ","
                . $this->var2str($this->codigo) . ","
                . $this->var2str($this->codpais) . ","
                . $this->var2str($this->codpago) . ","
                . $this->var2str($this->codpostal) . ","
                . $this->var2str($this->codserie) . ","
                . $this->var2str($this->direccion) . ","
                . $this->var2str($this->editable) . ","
                . $this->var2str($this->fecha) . ","
                . $this->var2str($this->finoferta) . ","
                . $this->var2str($this->hora) . ","
                . $this->var2str($this->idpedido) . ","
                . $this->var2str($this->irpf) . ","
                . $this->var2str($this->netosindto) . ","
                . $this->var2str($this->neto) . ","
                . $this->var2str($this->dtopor1) . ","
                . $this->var2str($this->dtopor2) . ","
                . $this->var2str($this->dtopor3) . ","
                . $this->var2str($this->dtopor4) . ","
                . $this->var2str($this->dtopor5) . ","
                . $this->var2str($this->nombrecliente) . ","
                . $this->var2str($this->numero) . ","
                . $this->var2str($this->observaciones) . ","
                . $this->var2str($this->status) . ","
                . $this->var2str($this->porcomision) . ","
                . $this->var2str($this->provincia) . ","
                . $this->var2str($this->tasaconv) . ","
                . $this->var2str($this->total) . ","
                . $this->var2str($this->totaleuros) . ","
                . $this->var2str($this->totalirpf) . ","
                . $this->var2str($this->totaliva) . ","
                . $this->var2str($this->totalrecargo) . ","
                . $this->var2str($this->numero2) . ","
                . $this->var2str($this->femail) . ","
                . $this->var2str($this->envio_codtrans) . ","
                . $this->var2str($this->envio_codigo) . ","
                . $this->var2str($this->envio_nombre) . ","
                . $this->var2str($this->envio_apellidos) . ","
                . $this->var2str($this->envio_apartado) . ","
                . $this->var2str($this->envio_direccion) . ","
                . $this->var2str($this->envio_codpostal) . ","
                . $this->var2str($this->envio_ciudad) . ","
                . $this->var2str($this->envio_provincia) . ","
                . $this->var2str($this->envio_codpais) . ","
                . $this->var2str($this->numdocs) . ","
                . $this->var2str($this->idoriginal) . ");";

            if ($this->db->exec($sql)) {
                $this->idpresupuesto = $this->db->lastval();
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Elimina el presupuesto de la base de datos.
     * Devuelve FALSE en caso de fallo, TRUE en caso de éxito.
     * @return boolean
     */
    public function delete()
    {
        $this->new_message(ucfirst(FS_PRESUPUESTO) . " de venta " . $this->codigo . " eliminado correctamente.");
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE idpresupuesto = " . $this->var2str($this->idpresupuesto) . ";");
    }

    /**
     * Devuelve un array con los últimos presupuestos de venta.
     * @param integer $offset
     * @param string $order
     * @return \presupuesto_cliente
     */
    public function all($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT)
    {
        $sql = "SELECT * FROM " . $this->table_name . " ORDER BY " . $order;

        $data = $this->db->select_limit($sql, $limit, $offset);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos de venta pendientes.
     * @param integer $offset
     * @param string $order
     * @return \presupuesto_cliente
     */
    public function all_ptepedir($offset = 0, $order = 'fecha ASC', $limit = FS_ITEM_LIMIT)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idpedido IS NULL"
            . " AND status = 0 ORDER BY " . $order;

        $data = $this->db->select_limit($sql, $limit, $offset);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos rechazados.
     * @param integer $offset
     * @param string $order
     * @return \presupuesto_cliente
     */
    public function all_rechazados($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE status = 2 ORDER BY " . $order;

        $data = $this->db->select_limit($sql, $limit, $offset);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos del cliente.
     * @param string $codcliente
     * @param integer $offset
     * @return \presupuesto_cliente
     */
    public function all_from_cliente($codcliente, $offset = 0)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcliente)
            . " ORDER BY fecha DESC, codigo DESC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos del agente/empleado.
     * @param string $codagente
     * @param integer $offset
     * @return \presupuesto_cliente
     */
    public function all_from_agente($codagente, $offset = 0)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codagente = " . $this->var2str($codagente)
            . " ORDER BY fecha DESC, codigo DESC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve todos los presupuestos relacionados con el pedido.
     * @param integer $id
     * @return \presupuesto_cliente
     */
    public function all_from_pedido($id)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE idpedido = " . $this->var2str($id)
            . " ORDER BY fecha DESC, codigo DESC;";

        $data = $this->db->select($sql);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos según los filtros:
     * 
     * @param string $desde
     * @param string $hasta
     * @param string $codserie
     * @param string $codagente
     * @param string $codcliente
     * @param string $estado
     * @param string $forma_pago
     * @param string $almacen
     * @param string $divisa
     * @return \presupuesto_cliente
     */
    public function all_desde($desde, $hasta, $codserie = FALSE, $codagente = FALSE, $codcliente = FALSE, $estado = FALSE, $forma_pago = FALSE, $almacen = FALSE, $divisa = FALSE)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE fecha >= " . $this->var2str($desde) . " AND fecha <= " . $this->var2str($hasta);
        if ($codserie) {
            $sql .= " AND codserie = " . $this->var2str($codserie);
        }
        if ($codagente) {
            $sql .= " AND codagente = " . $this->var2str($codagente);
        }
        if ($codcliente) {
            $sql .= " AND codcliente = " . $this->var2str($codcliente);
        }
        if ($estado) {
            if ($estado == '0') {
                $sql .= " AND idpedido is NULL AND status = 0";
            } else if ($estado == '1') {
                $sql .= " AND status = '1'";
            } else if ($this->estado == '2') {
                $sql .= " AND status = '2'";
            }
        }
        if ($forma_pago) {
            $sql .= " AND codpago = " . $this->var2str($forma_pago);
        }
        if ($divisa) {
            $sql .= "AND coddivisa = " . $this->var2str($divisa);
        }
        if ($almacen) {
            $sql .= "AND codalmacen = " . $this->var2str($almacen);
        }
        $sql .= " ORDER BY fecha ASC, codigo ASC;";

        $data = $this->db->select($sql);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos que coinciden con $query
     * @param string $query
     * @param integer $offset
     * @return \presupuesto_cliente
     */
    public function search($query, $offset = 0)
    {
        $query = mb_strtolower($this->no_html($query), 'UTF8');

        $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
        if (is_numeric($query)) {
            $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'
            OR total BETWEEN '" . ($query - .01) . "' AND '" . ($query + .01) . "'";
        } else if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query)) {
            /// es una fecha
            $consulta .= "fecha = " . $this->var2str($query) . " OR observaciones LIKE '%" . $query . "%'";
        } else {
            $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
        }
        $consulta .= " ORDER BY fecha DESC, codigo DESC";

        $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
        return $this->all_from_data($data);
    }

    /**
     * Devuelve un array con los presupuestos del cliente $codcliente que coincidan
     * con los filtros.
     * @param string $codcliente
     * @param string $desde
     * @param string $hasta
     * @param string $serie
     * @param string $obs
     * @return \presupuesto_cliente
     */
    public function search_from_cliente($codcliente, $desde, $hasta, $serie, $obs = '')
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcliente)
            . " AND idpedido AND fecha BETWEEN " . $this->var2str($desde) . " AND " . $this->var2str($hasta)
            . " AND codserie = " . $this->var2str($serie);

        if ($obs != '') {
            $sql .= " AND lower(observaciones) = " . $this->var2str(strtolower($obs));
        }

        $sql .= " ORDER BY fecha DESC, codigo DESC;";

        $data = $this->db->select($sql);
        return $this->all_from_data($data);
    }

    private function all_from_data(&$data)
    {
        $preslist = array();
        if ($data) {
            foreach ($data as $p) {
                $preslist[] = new \presupuesto_cliente($p);
            }
        }

        return $preslist;
    }

    public function cron_job()
    {
        /// marcamos como aprobados los presupuestos con idpedido
        $this->db->exec("UPDATE " . $this->table_name . " SET status = '1', editable = FALSE"
            . " WHERE status != '1' AND idpedido IS NOT NULL;");

        /// devolvemos al estado pendiente a los presupuestos con estado 1 a los que se haya borrado el pedido
        $this->db->exec("UPDATE " . $this->table_name . " SET status = '0', idpedido = NULL, editable = TRUE "
            . "WHERE status = '1' AND idpedido NOT IN (SELECT idpedido FROM pedidoscli);");

        /// marcamos como rechazados todos los presupuestos con finoferta ya pasada
        $this->db->exec("UPDATE " . $this->table_name . " SET status = '2' WHERE finoferta IS NOT NULL AND "
            . "finoferta < " . $this->var2str(Date('d-m-Y')) . " AND idpedido IS NULL;");

        /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
        $this->db->exec("UPDATE " . $this->table_name . " SET status = '2' WHERE idpedido IS NULL AND "
            . "editable = false;");
        
        /// asignamos netosindto a neto a todos los que estén a 0
        $this->db->exec("UPDATE " . $this->table_name . " SET netosindto = neto WHERE netosindto = 0;");
    }
}
