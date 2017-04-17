<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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

require_model('albaran_cliente.php');
require_model('cliente.php');
require_model('linea_pedido_cliente.php');
require_model('secuencia.php');

/**
 * Pedido de cliente
 */
class pedido_cliente extends \fs_model
{
   /**
    * Clave primaria.
    * @var type 
    */
   public $idpedido;
   
   /**
    * ID del albarán relacionado.
    * @var type 
    */
   public $idalbaran;
   
   /**
    * Código único. Para humanos.
    * @var type 
    */
   public $codigo;
   
   /**
    * Serie relacionada.
    * @var type 
    */
   public $codserie;
   
   /**
    * Ejercicio relacionado. El que corresponde a la fecha.
    * @var type 
    */
   public $codejercicio;
   
   /**
    * Código del cliente del pedido.
    * @var type 
    */
   public $codcliente;
   
   /**
    * Empleado que ha creado el pedido.
    * @var type 
    */
   public $codagente;
   
   /**
    * Forma de pago asociada.
    * @var type 
    */
   public $codpago;
   
   /**
    * Divisa del pedido.
    * @var type 
    */
   public $coddivisa;
   
   /**
    * Almacén del que saldrá el material
    * @var type 
    */
   public $codalmacen;
   
   /**
    * País del cliente.
    * @var type 
    */
   public $codpais;
   
   /**
    * ID de la dirección del cliente.
    * Modelo direccion_cliente.
    * @var type 
    */
   public $coddir;
   public $codpostal;
   
   /**
    * Número del pedido.
    * Único dentro de la serie+ejercicio.
    * @var type 
    */
   public $numero;

   /**
    * Número opcional a disposición del usuario.
    * @var type 
    */
   public $numero2;
   
   public $nombrecliente;
   public $cifnif;
   public $direccion;
   public $ciudad;
   public $provincia;
   public $apartado;
   public $fecha;
   public $hora;
   
   /**
    * Importe total antes de impuestos.
    * Es la suma del pvptotal de las líneas.
    * @var type 
    */
   public $neto;
   
   /**
    * Importe total de la factura, con impuestos.
    * @var type 
    */
   public $total;
   
   /**
    * Suma del IVA de las líneas.
    * @var type 
    */
   public $totaliva;
   
   /**
    * Total expresado en euros, por si no fuese la divisa del pedido.
    * totaleuros = total/tasaconv
    * No hace falta rellenarlo, al hacer save() se calcula el valor.
    * @var type 
    */
   public $totaleuros;
   
   /**
    * % de retención IRPF del pedido. Se obtiene de la serie.
    * Cada línea puede tener un % distinto.
    * @var type 
    */
   public $irpf;
   
   /**
    * Suma de las retenciones IRPF de las líneas del pedido.
    * @var type 
    */
   public $totalirpf;
   
   /**
    * % de comisión del empleado.
    * @var type 
    */
   public $porcomision;
   
   /**
    * Tasa de conversión a Euros de la divisa seleccionada.
    * @var type 
    */
   public $tasaconv;
   
   /**
    * Suma total del recargo de equivalencia de las líneas.
    * @var type 
    */
   public $totalrecargo;
   
   public $observaciones;
   
   /**
    * Estado del pedido:
    * 0 -> pendiente. (editable)
    * 1 -> aprobado. (hay un idalbaran y no es editable)
    * 2 -> rechazado. (no hay idalbaran y no es editable)
    * 3 -> validado parcialmente.
    * @var type 
    */
   public $status;
   
   public $editable;
   
   /**
    * Fecha en la que se envió el pedido por email.
    * @var type 
    */
   public $femail;
   
   /**
    * Fecha de salida prevista del material.
    * @var type 
    */
   public $fechasalida;
   
   /// datos de transporte
   public $envio_codtrans;
   public $envio_codigo;
   public $envio_nombre;
   public $envio_apellidos;
   public $envio_apartado;
   public $envio_direccion;
   public $envio_codpostal;
   public $envio_ciudad;
   public $envio_provincia;
   public $envio_codpais;
   
   /**
    * Número de documentos adjuntos.
    * @var integer 
    */
   public $numdocs;
   
   /**
    * Si este presupuesto es la versión de otro, aquí se almacena el idpresupuesto del original.
    * @var type 
    */
   public $idoriginal;
   
   public function __construct($p = FALSE)
   {
      parent::__construct('pedidoscli');
      if ($p)
      {
         $this->idpedido = $this->intval($p['idpedido']);
         $this->idalbaran = $this->intval($p['idalbaran']);
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

         $this->hora = Date('H:i:s', strtotime($p['fecha']));
         if( !is_null($p['hora']) )
         {
            $this->hora = date('H:i:s', strtotime($p['hora']));
         }

         $this->neto = floatval($p['neto']);
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
         if($this->idalbaran)
         {
            $this->status = 1;
            $this->editable = FALSE;
         }
         else if($this->status == 2)
         {
            /// cancelado o validado parcialmente
            $this->editable = FALSE;
         }
         else if($this->editable)
         {
            $this->status = 0;
         }
         else
         {
            $this->status = 2;
         }
         
         $this->femail = NULL;
         if( !is_null($p['femail']) )
         {
            $this->femail = Date('d-m-Y', strtotime($p['femail']));
         }
         
         $this->fechasalida = NULL;
         if( !is_null($p['fechasalida']) )
         {
            $this->fechasalida = Date('d-m-Y', strtotime($p['fechasalida']));
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
      }
      else
      {
         $this->idpedido = NULL;
         $this->idalbaran = NULL;
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
         $this->hora = Date('H:i:s');
         $this->neto = 0;
         $this->total = 0;
         $this->totaliva = 0;
         $this->totaleuros = 0;
         $this->irpf = 0;
         $this->totalirpf = 0;
         $this->porcomision = 0;
         $this->tasaconv = 1;
         $this->totalrecargo = 0;
         $this->observaciones = NULL;
         $this->status = 0;
         $this->editable = TRUE;
         $this->femail = NULL;
         $this->fechasalida = NULL;
         
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
      if ($s)
      {
         return Date('H:i:s', strtotime($this->hora));
      }
      else
         return Date('H:i', strtotime($this->hora));
   }

   public function observaciones_resume()
   {
      if ($this->observaciones == '')
      {
         return '-';
      }
      else if (strlen($this->observaciones) < 60)
      {
         return $this->observaciones;
      }
      else
         return substr($this->observaciones, 0, 50) . '...';
   }

   public function url()
   {
      if (is_null($this->idpedido))
      {
         return 'index.php?page=ventas_pedidos';
      }
      else
         return 'index.php?page=ventas_pedido&id=' . $this->idpedido;
   }

   public function albaran_url()
   {
      if (is_null($this->idalbaran))
      {
         return 'index.php?page=ventas_albaran';
      }
      else
         return 'index.php?page=ventas_albaran&id=' . $this->idalbaran;
   }

   public function agente_url()
   {
      if (is_null($this->codagente))
      {
         return "index.php?page=admin_agentes";
      }
      else
         return "index.php?page=admin_agente&cod=" . $this->codagente;
   }

   public function cliente_url()
   {
      if (is_null($this->codcliente))
      {
         return "index.php?page=ventas_clientes";
      }
      else
         return "index.php?page=ventas_cliente&cod=" . $this->codcliente;
   }
   
   /**
    * Devuelve las líneas del pedido.
    * @return \linea_pedido_cliente
    */
   public function get_lineas()
   {
      $linea = new \linea_pedido_cliente();
      return $linea->all_from_pedido($this->idpedido);
   }
   
   public function get_versiones()
   {
      $versiones = array();
      
      $sql = "SELECT * FROM " . $this->table_name . " WHERE idoriginal = " . $this->var2str($this->idpedido);
      if($this->idoriginal)
      {
         $sql .= " OR idoriginal = " . $this->var2str($this->idoriginal);
         $sql .= " OR idpedido = " . $this->var2str($this->idoriginal);
      }
      $sql .= "ORDER BY fecha DESC, hora DESC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
         {
            $versiones[] = new \pedido_cliente($d);
         }
      }
      
      return $versiones;
   }

   public function get($id)
   {
      $pedido = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idpedido = " . $this->var2str($id) . ";");
      if($pedido)
      {
         return new \pedido_cliente($pedido[0]);
      }
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->idpedido) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE idpedido = " . $this->var2str($this->idpedido) . ";");
   }
   
   /**
    * Genera un nuevo código y número para el pedido
    */
   public function new_codigo()
   {
      $sec = new \secuencia();
      $sec = $sec->get_by_params2($this->codejercicio, $this->codserie, 'npedidocli');
      if($sec)
      {
         $this->numero = $sec->valorout;
         $sec->valorout++;
         $sec->save();
      }

      if(!$sec OR $this->numero <= 1)
      {
         $numero = $this->db->select("SELECT MAX(" . $this->db->sql_to_int('numero') . ") as num
            FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($this->codejercicio) .
                 " AND codserie = " . $this->var2str($this->codserie) . ";");
         if($numero)
         {
            $this->numero = 1 + intval($numero[0]['num']);
         }
         else
            $this->numero = 1;

         if($sec)
         {
            $sec->valorout = 1 + $this->numero;
            $sec->save();
         }
      }
      
      if(FS_NEW_CODIGO == 'eneboo')
      {
         $this->codigo = $this->codejercicio.sprintf('%02s', $this->codserie).sprintf('%06s', $this->numero);
      }
      else
      {
         $this->codigo = strtoupper(substr(FS_PEDIDO, 0, 3)).$this->codejercicio.$this->codserie.$this->numero;
      }
   }
   
   /**
    * Comprueba los datos del pedido, devuelve TRUE si está todo correcto
    * @return boolean
    */
   public function test()
   {
      $this->nombrecliente = $this->no_html($this->nombrecliente);
      if($this->nombrecliente == '')
      {
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
      if($this->idalbaran)
      {
         $this->status = 1;
         $this->editable = FALSE;
      }
      else if($this->status == 0)
      {
         $this->editable = TRUE;
      }
      else if($this->status == 2)
      {
         $this->editable = FALSE;
      }
      
      if($this->floatcmp($this->total, $this->neto + $this->totaliva - $this->totalirpf + $this->totalrecargo, FS_NF0, TRUE))
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg("Error grave: El total está mal calculado. ¡Informa del error!");
         return FALSE;
      }
   }

   public function full_test($duplicados = TRUE)
   {
      $status = TRUE;

      /// comprobamos las líneas
      $neto = 0;
      $iva = 0;
      $irpf = 0;
      $recargo = 0;
      foreach ($this->get_lineas() as $l)
      {
         if( !$l->test() )
         {
            $status = FALSE;
         }

         $neto += $l->pvptotal;
         $iva += $l->pvptotal * $l->iva / 100;
         $irpf += $l->pvptotal * $l->irpf / 100;
         $recargo += $l->pvptotal * $l->recargo / 100;
      }

      $neto = round($neto, FS_NF0);
      $iva = round($iva, FS_NF0);
      $irpf = round($irpf, FS_NF0);
      $recargo = round($recargo, FS_NF0);
      $total = $neto + $iva - $irpf + $recargo;

      if (!$this->floatcmp($this->neto, $neto, FS_NF0, TRUE))
      {
         $this->new_error_msg("Valor neto de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $neto);
         $status = FALSE;
      }
      else if (!$this->floatcmp($this->totaliva, $iva, FS_NF0, TRUE))
      {
         $this->new_error_msg("Valor totaliva de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $iva);
         $status = FALSE;
      }
      else if (!$this->floatcmp($this->totalirpf, $irpf, FS_NF0, TRUE))
      {
         $this->new_error_msg("Valor totalirpf de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $irpf);
         $status = FALSE;
      }
      else if (!$this->floatcmp($this->totalrecargo, $recargo, FS_NF0, TRUE))
      {
         $this->new_error_msg("Valor totalrecargo de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $recargo);
         $status = FALSE;
      }
      else if (!$this->floatcmp($this->total, $total, FS_NF0, TRUE))
      {
         $this->new_error_msg("Valor total de " . FS_PEDIDO . " incorrecto. Valor correcto: " . $total);
         $status = FALSE;
      }

      if($this->idalbaran)
      {
         $alb0 = new \albaran_cliente();
         $albaran = $alb0->get($this->idalbaran);
         if(!$albaran)
         {
            $this->idalbaran = NULL;
            $this->status = 0;
            $this->editable = TRUE;
            $this->save();
         }
      }

      return $status;
   }

   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET apartado = ".$this->var2str($this->apartado)
                    . ", cifnif = ".$this->var2str($this->cifnif)
                    . ", ciudad = ".$this->var2str($this->ciudad)
                    . ", codagente = ".$this->var2str($this->codagente)
                    . ", codalmacen = ".$this->var2str($this->codalmacen)
                    . ", codcliente = ".$this->var2str($this->codcliente)
                    . ", coddir = ".$this->var2str($this->coddir)
                    . ", coddivisa = ".$this->var2str($this->coddivisa)
                    . ", codejercicio = ".$this->var2str($this->codejercicio)
                    . ", codigo = ".$this->var2str($this->codigo)
                    . ", codpago = ".$this->var2str($this->codpago)
                    . ", codpais = ".$this->var2str($this->codpais)
                    . ", codpostal = ".$this->var2str($this->codpostal)
                    . ", codserie = ".$this->var2str($this->codserie)
                    . ", direccion = ".$this->var2str($this->direccion)
                    . ", editable = ".$this->var2str($this->editable)
                    . ", fecha = ".$this->var2str($this->fecha)
                    . ", hora = ".$this->var2str($this->hora)
                    . ", idalbaran = ".$this->var2str($this->idalbaran)
                    . ", irpf = ".$this->var2str($this->irpf)
                    . ", neto = ".$this->var2str($this->neto)
                    . ", nombrecliente = ".$this->var2str($this->nombrecliente)
                    . ", numero = ".$this->var2str($this->numero)
                    . ", numero2 = ".$this->var2str($this->numero2)
                    . ", observaciones = ".$this->var2str($this->observaciones)
                    . ", status = ".$this->var2str($this->status)
                    . ", porcomision = ".$this->var2str($this->porcomision)
                    . ", provincia = ".$this->var2str($this->provincia)
                    . ", tasaconv = ".$this->var2str($this->tasaconv)
                    . ", total = ".$this->var2str($this->total)
                    . ", totaleuros = ".$this->var2str($this->totaleuros)
                    . ", totalirpf = ".$this->var2str($this->totalirpf)
                    . ", totaliva = ".$this->var2str($this->totaliva)
                    . ", totalrecargo = ".$this->var2str($this->totalrecargo)
                    . ", femail = ".$this->var2str($this->femail)
                    . ", fechasalida = ".$this->var2str($this->fechasalida)
                    . ", codtrans = ".$this->var2str($this->envio_codtrans)
                    . ", codigoenv = ".$this->var2str($this->envio_codigo)
                    . ", nombreenv = ".$this->var2str($this->envio_nombre)
                    . ", apellidosenv = ".$this->var2str($this->envio_apellidos)
                    . ", apartadoenv = ".$this->var2str($this->envio_apartado)
                    . ", direccionenv = ".$this->var2str($this->envio_direccion)
                    . ", codpostalenv = ".$this->var2str($this->envio_codpostal)
                    . ", ciudadenv = ".$this->var2str($this->envio_ciudad)
                    . ", provinciaenv = ".$this->var2str($this->envio_provincia)
                    . ", codpaisenv = ".$this->var2str($this->envio_codpais)
                    . ", numdocs = ".$this->var2str($this->numdocs)
                    . ", idoriginal = ".$this->var2str($this->idoriginal)
                    . "  WHERE idpedido = ".$this->var2str($this->idpedido).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $this->new_codigo();
            $sql = "INSERT INTO ".$this->table_name." (apartado,cifnif,ciudad,codagente,codalmacen,
               codcliente,coddir,coddivisa,codejercicio,codigo,codpais,codpago,codpostal,codserie,
               direccion,editable,fecha,hora,idalbaran,irpf,neto,nombrecliente,numero,observaciones,
               status,porcomision,provincia,tasaconv,total,totaleuros,totalirpf,totaliva,totalrecargo,
               numero2,femail,fechasalida,codtrans,codigoenv,nombreenv,apellidosenv,apartadoenv,direccionenv,
               codpostalenv,ciudadenv,provinciaenv,codpaisenv,numdocs,idoriginal) VALUES ("
                    . $this->var2str($this->apartado).","
                    . $this->var2str($this->cifnif).","
                    . $this->var2str($this->ciudad).","
                    . $this->var2str($this->codagente).","
                    . $this->var2str($this->codalmacen).","
                    . $this->var2str($this->codcliente).","
                    . $this->var2str($this->coddir).","
                    . $this->var2str($this->coddivisa).","
                    . $this->var2str($this->codejercicio).","
                    . $this->var2str($this->codigo).","
                    . $this->var2str($this->codpais).","
                    . $this->var2str($this->codpago).","
                    . $this->var2str($this->codpostal).","
                    . $this->var2str($this->codserie).","
                    . $this->var2str($this->direccion).","
                    . $this->var2str($this->editable).","
                    . $this->var2str($this->fecha).","
                    . $this->var2str($this->hora).","
                    . $this->var2str($this->idalbaran).","
                    . $this->var2str($this->irpf).","
                    . $this->var2str($this->neto).","
                    . $this->var2str($this->nombrecliente).","
                    . $this->var2str($this->numero).","
                    . $this->var2str($this->observaciones).","
                    . $this->var2str($this->status).","
                    . $this->var2str($this->porcomision).","
                    . $this->var2str($this->provincia).","
                    . $this->var2str($this->tasaconv).","
                    . $this->var2str($this->total).","
                    . $this->var2str($this->totaleuros).","
                    . $this->var2str($this->totalirpf).","
                    . $this->var2str($this->totaliva).","
                    . $this->var2str($this->totalrecargo).","
                    . $this->var2str($this->numero2).","
                    . $this->var2str($this->femail).","
                    . $this->var2str($this->fechasalida).","
                    . $this->var2str($this->envio_codtrans).","
                    . $this->var2str($this->envio_codigo).","
                    . $this->var2str($this->envio_nombre).","
                    . $this->var2str($this->envio_apellidos).","
                    . $this->var2str($this->envio_apartado).","
                    . $this->var2str($this->envio_direccion).","
                    . $this->var2str($this->envio_codpostal).","
                    . $this->var2str($this->envio_ciudad).","
                    . $this->var2str($this->envio_provincia).","
                    . $this->var2str($this->envio_codpais).","
                    . $this->var2str($this->numdocs).","
                    . $this->var2str($this->idoriginal).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idpedido = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   /**
    * Elimina el pedido de la base de datos.
    * Devuelve FALSE en caso de fallo.
    * @return boolean
    */
   public function delete()
   {
      if( $this->db->exec("DELETE FROM ".$this->table_name." WHERE idpedido = ".$this->var2str($this->idpedido).";") )
      {
         /// modificamos el presupuesto relacionado
         $this->db->exec("UPDATE presupuestoscli SET idpedido = NULL, editable = TRUE,"
                 . " status = 0 WHERE idpedido = " . $this->var2str($this->idpedido) . ";");
         
         $this->new_message(ucfirst(FS_PEDIDO).' de venta '.$this->codigo." eliminado correctamente.");
         return TRUE;
      }
      else
         return FALSE;
   }
   
   /**
    * Devuelve un array con los pedidos de venta.
    * @param type $offset
    * @param type $order
    * @return \pedido_cliente
    */
   public function all($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT)
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." ORDER BY ".$order;
      
      $data = $this->db->select_limit($sql, $limit, $offset);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   /**
    * Devuelve un array con los pedidos de venta pendientes
    * @param type $offset
    * @param type $order
    * @return \pedido_cliente
    */
   public function all_ptealbaran($offset = 0, $order = 'fecha ASC', $limit = FS_ITEM_LIMIT)
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE idalbaran IS NULL AND status = 0 ORDER BY ".$order;
      
      $data = $this->db->select_limit($sql, $limit, $offset);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   /**
    * Devuelve un array con los pedidos de venta rechazados
    * @param type $offset
    * @param type $order
    * @return \pedido_cliente
    */
   public function all_rechazados($offset = 0, $order = 'fecha DESC', $limit = FS_ITEM_LIMIT)
   {
      $preclist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE status = 2 ORDER BY ".$order;
      
      $data = $this->db->select_limit($sql, $limit, $offset);
      if($data)
      {
         foreach($data as $p)
         {
            $preclist[] = new \pedido_cliente($p);
         }
      }
      
      return $preclist;
   }
   
   /**
    * Devuelve un array con los pedidos del cliente $codcliente
    * @param type $codcliente
    * @param type $offset
    * @return \pedido_cliente
    */
   public function all_from_cliente($codcliente, $offset = 0)
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE codcliente = ".$this->var2str($codcliente)
              ." ORDER BY fecha DESC, codigo DESC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   /**
    * Devuelve un array con los pedidos del agente/empleado
    * @param type $codagente
    * @param type $offset
    * @return \pedido_cliente
    */
   public function all_from_agente($codagente, $offset = 0)
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE codagente = ".$this->var2str($codagente)
              ." ORDER BY fecha DESC, codigo DESC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   /**
    * Devuelve todos los pedidos relacionados con el albarán.
    * @param type $id
    * @return \pedido_cliente
    */
   public function all_from_albaran($id)
   {
      $pedilist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE idalbaran = ".$this->var2str($id)
              ." ORDER BY fecha DESC, codigo DESC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   /**
    * Devuelve un array con los pedidos entre $desde y $hasta
    * @param type $desde
    * @param type $hasta
    * @return \pedido_cliente
    */
   public function all_desde($desde, $hasta)
   {
      $pedlist = array();
      $sql = "SELECT * FROM ".$this->table_name." WHERE fecha >= ".$this->var2str($desde)
              ." AND fecha <= ".$this->var2str($hasta)." ORDER BY codigo ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $p)
         {
            $pedlist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedlist;
   }
   
   /**
    * Devuelve un array con todos los pedidos que coinciden con $query
    * @param type $query
    * @param type $offset
    * @return \pedido_cliente
    */
   public function search($query, $offset = 0)
   {
      $pedilist = array();
      $query = strtolower($this->no_html($query));

      $consulta = "SELECT * FROM " . $this->table_name . " WHERE ";
      if( is_numeric($query) )
      {
         $consulta .= "codigo LIKE '%" . $query . "%' OR numero2 LIKE '%" . $query . "%' OR observaciones LIKE '%" . $query . "%'
            OR total BETWEEN '" . ($query - .01) . "' AND '" . ($query + .01) . "'";
      }
      else if( preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i', $query) )
      {
         /// es una fecha
         $consulta .= "fecha = " . $this->var2str($query) . " OR observaciones LIKE '%" . $query . "%'";
      }
      else
      {
         $consulta .= "lower(codigo) LIKE '%" . $query . "%' OR lower(numero2) LIKE '%" . $query . "%' "
                 . "OR lower(observaciones) LIKE '%" . str_replace(' ', '%', $query) . "%'";
      }
      $consulta .= " ORDER BY fecha DESC, codigo DESC";

      $data = $this->db->select_limit($consulta, FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   /**
    * Devuelve un array con todos los pedidos que coincicen con $query del cliente $codcliente
    * @param type $codcliente
    * @param type $desde
    * @param type $hasta
    * @param type $serie
    * @param type $obs
    * @return \pedido_cliente
    */
   public function search_from_cliente($codcliente, $desde, $hasta, $serie, $obs = '')
   {
      $pedilist = array();
      
      $sql = "SELECT * FROM " . $this->table_name . " WHERE codcliente = " . $this->var2str($codcliente) .
              " AND idalbaran AND fecha BETWEEN " . $this->var2str($desde) . " AND " . $this->var2str($hasta) .
              " AND codserie = " . $this->var2str($serie);

      if($obs != '')
      {
         $sql .= " AND lower(observaciones) = " . $this->var2str(strtolower($obs));
      }

      $sql .= " ORDER BY fecha DESC, codigo DESC;";

      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $p)
         {
            $pedilist[] = new \pedido_cliente($p);
         }
      }
      
      return $pedilist;
   }
   
   public function cron_job()
   {
      /// marcamos como aprobados los presupuestos con idpedido
      $this->db->exec("UPDATE ".$this->table_name." SET status = '1', editable = FALSE"
              . " WHERE status != '1' AND idalbaran IS NOT NULL;");
      
      /// devolvemos al estado pendiente a los pedidos con estado 1 a los que se haya borrado el albarán
      $this->db->exec("UPDATE ".$this->table_name." SET status = '0', idalbaran = NULL, editable = TRUE "
              . "WHERE status = '1' AND idalbaran NOT IN (SELECT idalbaran FROM albaranescli);");
      
      /// marcamos como rechazados todos los presupuestos no editables y sin pedido asociado
      $this->db->exec("UPDATE pedidoscli SET status = '2' WHERE idalbaran IS NULL AND"
              . " editable = false;");
   }
}
