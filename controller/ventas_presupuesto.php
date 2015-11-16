<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('pedido_cliente.php');
require_model('fabricante.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('linea_presupuesto_cliente.php');
require_model('pais.php');
require_model('presupuesto_cliente.php');
require_model('regularizacion_iva.php');
require_model('serie.php');

class ventas_presupuesto extends fs_controller {

   public $agente;
   public $cliente;
   public $cliente_s;
   public $divisa;
   public $ejercicio;
   public $fabricante;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $nuevo_presupuesto_url;
   public $pais;
   public $presupuesto;
   public $serie;

   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PRESUPUESTO), 'ventas', FALSE, FALSE);
   }

   protected function private_core()
   {
      $this->ppage = $this->page->get('ventas_presupuestos');
      $this->agente = FALSE;

      $presupuesto = new presupuesto_cliente();
      $this->presupuesto = FALSE;
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
      
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_venta,
       * necesario para poder añadir líneas.
       */
      if( $this->user->have_access_to('nueva_venta', FALSE) )
      {
         $nuevoprep = $this->page->get('nueva_venta');
         if($nuevoprep)
            $this->nuevo_presupuesto_url = $nuevoprep->url();
      }

      if( isset($_POST['idpresupuesto']) )
      {
         $this->presupuesto = $presupuesto->get($_POST['idpresupuesto']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->presupuesto = $presupuesto->get($_GET['id']);
      }

      if($this->presupuesto)
      {
         $this->page->title = $this->presupuesto->codigo;

         /// cargamos el agente
         if (!is_null($this->presupuesto->codagente))
         {
            $agente = new agente();
            $this->agente = $agente->get($this->presupuesto->codagente);
         }

         /// cargamos el cliente
         $this->cliente_s = $this->cliente->get($this->presupuesto->codcliente);

         /// comprobamos el presupuesto
         if( $this->presupuesto->full_test() )
         {
            if (isset($_REQUEST['status']))
            {
               $this->presupuesto->status = intval($_REQUEST['status']);
               
               if($this->presupuesto->status == 1 AND is_null($this->presupuesto->idpedido))
               {
                  $this->generar_pedido();
               }
               else if( $this->presupuesto->save() )
               {
                  $this->new_message(ucfirst(FS_PRESUPUESTO)." modificado correctamente.");
               }
               else
               {
                  $this->new_error_msg("¡Imposible modificar el ".FS_PRESUPUESTO."!");
               }
            }
            else
            {
               /// Comprobamos las líneas
               $this->check_lineas();
            }
         }
      }
      else
         $this->new_error_msg("¡" . ucfirst(FS_PRESUPUESTO) . " de cliente no encontrado!");
   }

   /**
    * Comprobamos si los artículos han variado su precio.
    * @return type
    */
   private function check_lineas()
   {
      if($this->presupuesto->status == 0)
      {
         foreach($this->presupuesto->get_lineas() as $l)
         {
            if($l->referencia != '')
            {
               $data = $this->db->select("SELECT factualizado,pvp FROM articulos WHERE referencia = " . $l->var2str($l->referencia) . " ORDER BY referencia ASC;");
               if($data)
               {
                  if( strtotime($data[0]["factualizado"]) > strtotime($this->presupuesto->fecha) )
                  {
                     if( $l->pvpunitario > floatval($data[0]['pvp']) )
                     {
                        $this->new_advice("El precio del artículo <a href='" . $l->articulo_url() . "'>" . $l->referencia . "</a>"
                              . " ha bajado desde la elaboración del " . FS_PRESUPUESTO . ".");
                     }
                     else if( $l->pvpunitario < floatval($data[0]['pvp']) )
                     {
                        $this->new_advice("El precio del artículo <a href='" . $l->articulo_url() . "'>" . $l->referencia . "</a>"
                                . " ha subido desde la elaboración del " . FS_PRESUPUESTO . ".");
                     }
                  }
               }
            }
         }
      }
   }

   public function url()
   {
      if (!isset($this->presupuesto))
      {
         return parent::url();
      }
      else if ($this->presupuesto)
      {
         return $this->presupuesto->url();
      }
      else
         return $this->page->url();
   }

   private function modificar()
   {
      $this->presupuesto->observaciones = $_POST['observaciones'];
      $this->presupuesto->numero2 = $_POST['numero2'];

      if($this->presupuesto->editable)
      {
         /// obtenemos los datos del ejercicio para acotar la fecha
         $eje0 = $this->ejercicio->get($this->presupuesto->codejercicio);
         if($eje0)
         {
            $this->presupuesto->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
            $this->presupuesto->hora = $_POST['hora'];
            
            $this->presupuesto->finoferta = NULL;
            if($_POST['finoferta'] != '')
            {
               $this->presupuesto->finoferta = $_POST['finoferta'];
            }
         }
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado al '.FS_PRESUPUESTO);

         /// ¿cambiamos el cliente?
         if ($_POST['cliente'] != $this->presupuesto->codcliente)
         {
            $cliente = $this->cliente->get($_POST['cliente']);
            if ($cliente)
            {
               foreach ($cliente->get_direcciones() as $d)
               {
                  if ($d->domfacturacion)
                  {
                     $this->presupuesto->codcliente = $cliente->codcliente;
                     $this->presupuesto->cifnif = $cliente->cifnif;
                     $this->presupuesto->nombrecliente = $cliente->razonsocial;
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
            }
            else
               die('No se ha encontrado el cliente.');
         }
         else
         {
            $this->presupuesto->nombrecliente = $_POST['nombrecliente'];
            $this->presupuesto->cifnif = $_POST['cifnif'];
            $this->presupuesto->codpais = $_POST['codpais'];
            $this->presupuesto->provincia = $_POST['provincia'];
            $this->presupuesto->ciudad = $_POST['ciudad'];
            $this->presupuesto->codpostal = $_POST['codpostal'];
            $this->presupuesto->direccion = $_POST['direccion'];
            
            $cliente = $this->cliente->get($this->presupuesto->codcliente);
         }

         $serie = $this->serie->get($this->presupuesto->codserie);

         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->presupuesto->codserie)
         {
            $serie2 = $this->serie->get($_POST['serie']);
            if($serie2)
            {
               $this->presupuesto->codserie = $serie2->codserie;
               $this->presupuesto->irpf = $serie2->irpf;
               $this->presupuesto->new_codigo();

               $serie = $serie2;
            }
         }
         
         $this->presupuesto->codpago = $_POST['forma_pago'];
         
         /// ¿Cambiamos la divisa?
         if($_POST['divisa'] != $this->presupuesto->coddivisa)
         {
            $divisa = $this->divisa->get($_POST['divisa']);
            if($divisa)
            {
               $this->presupuesto->coddivisa = $divisa->coddivisa;
               $this->presupuesto->tasaconv = $divisa->tasaconv;
            }
         }
         else if($_POST['tasaconv'] != '')
         {
            $this->presupuesto->tasaconv = floatval($_POST['tasaconv']);
         }
         
         if( isset($_POST['numlineas']) )
         {
            $numlineas = intval($_POST['numlineas']);

            $this->presupuesto->neto = 0;
            $this->presupuesto->totaliva = 0;
            $this->presupuesto->totalirpf = 0;
            $this->presupuesto->totalrecargo = 0;
            $lineas = $this->presupuesto->get_lineas();
            $articulo = new articulo();

            /// eliminamos las líneas que no encontremos en el $_POST
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               for($num = 0; $num <= $numlineas; $num++)
               {
                  if( isset($_POST['idlinea_'.$num]) )
                  {
                     if($l->idlinea == intval($_POST['idlinea_'.$num]))
                     {
                        $encontrada = TRUE;
                        break;
                     }
                  }
               }
               if (!$encontrada)
               {
                  if (!$l->delete())
                     $this->new_error_msg("¡Imposible eliminar la línea del artículo " . $l->referencia . "!");
               }
            }

            /// modificamos y/o añadimos las demás líneas
            for ($num = 0; $num <= $numlineas; $num++)
            {
               $encontrada = FALSE;
               if (isset($_POST['idlinea_' . $num]))
               {
                  foreach ($lineas as $k => $value)
                  {
                     /// modificamos la línea
                     if ($value->idlinea == intval($_POST['idlinea_' . $num]))
                     {
                        $encontrada = TRUE;
                        $lineas[$k]->cantidad = floatval($_POST['cantidad_' . $num]);
                        $lineas[$k]->pvpunitario = floatval($_POST['pvp_' . $num]);
                        $lineas[$k]->dtopor = floatval($_POST['dto_' . $num]);
                        $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                        $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor) / 100);
                        $lineas[$k]->descripcion = $_POST['desc_' . $num];

                        $lineas[$k]->codimpuesto = NULL;
                        $lineas[$k]->iva = 0;
                        $lineas[$k]->recargo = 0;
                        $lineas[$k]->irpf = floatval($_POST['irpf_' . $num]);
                        if(!$serie->siniva AND $cliente->regimeniva != 'Exento')
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                           if($imp0)
                           {
                              $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                           }

                           $lineas[$k]->iva = floatval($_POST['iva_' . $num]);
                           $lineas[$k]->recargo = floatval($_POST['recargo_' . $num]);
                        }

                        if ($lineas[$k]->save())
                        {
                           $this->presupuesto->neto += $value->pvptotal;
                           $this->presupuesto->totaliva += $value->pvptotal * $value->iva / 100;
                           $this->presupuesto->totalirpf += $value->pvptotal * $value->irpf / 100;
                           $this->presupuesto->totalrecargo += $value->pvptotal * $value->recargo / 100;
                        }
                        else
                           $this->new_error_msg("¡Imposible modificar la línea del artículo " . $value->referencia . "!");
                        break;
                     }
                  }

                  /// añadimos la línea
                  if (!$encontrada AND intval($_POST['idlinea_' . $num]) == -1 AND isset($_POST['referencia_' . $num]))
                  {
                     $linea = new linea_presupuesto_cliente();
                     $linea->idpresupuesto = $this->presupuesto->idpresupuesto;
                     $linea->descripcion = $_POST['desc_' . $num];
                     
                     if (!$serie->siniva AND $cliente->regimeniva != 'Exento')
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                        if($imp0)
                           $linea->codimpuesto = $imp0->codimpuesto;
                        
                        $linea->iva = floatval($_POST['iva_' . $num]);
                        $linea->recargo = floatval($_POST['recargo_' . $num]);
                     }
                     
                     $linea->irpf = floatval($_POST['irpf_'.$num]);
                     $linea->cantidad = floatval($_POST['cantidad_' . $num]);
                     $linea->pvpunitario = floatval($_POST['pvp_' . $num]);
                     $linea->dtopor = floatval($_POST['dto_' . $num]);
                     $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                     $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor) / 100);
                     
                     $art0 = $articulo->get($_POST['referencia_' . $num]);
                     if($art0)
                     {
                        $linea->referencia = $art0->referencia;
                     }
                     
                     if( $linea->save() )
                     {
                        $this->presupuesto->neto += $linea->pvptotal;
                        $this->presupuesto->totaliva += $linea->pvptotal * $linea->iva / 100;
                        $this->presupuesto->totalirpf += $linea->pvptotal * $linea->irpf / 100;
                        $this->presupuesto->totalrecargo += $linea->pvptotal * $linea->recargo / 100;
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "!");
                  }
               }
            }

            /// redondeamos
            $this->presupuesto->neto = round($this->presupuesto->neto, FS_NF0);
            $this->presupuesto->totaliva = round($this->presupuesto->totaliva, FS_NF0);
            $this->presupuesto->totalirpf = round($this->presupuesto->totalirpf, FS_NF0);
            $this->presupuesto->totalrecargo = round($this->presupuesto->totalrecargo, FS_NF0);
            $this->presupuesto->total = $this->presupuesto->neto + $this->presupuesto->totaliva - $this->presupuesto->totalirpf + $this->presupuesto->totalrecargo;

            if (abs(floatval($_POST['atotal']) - $this->presupuesto->total) >= .02)
            {
               $this->new_error_msg("El total difiere entre el controlador y la vista (" . $this->presupuesto->total .
                       " frente a " . $_POST['atotal'] . "). Debes informar del error.");
            }
         }
      }

      if ($this->presupuesto->save())
      {
         $this->new_message(ucfirst(FS_PRESUPUESTO) . " modificado correctamente.");
         $this->new_change(ucfirst(FS_PRESUPUESTO) . ' Cliente ' . $this->presupuesto->codigo, $this->presupuesto->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el " . FS_PRESUPUESTO . "!");
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
      $pedido->neto = $this->presupuesto->neto;
      $pedido->nombrecliente = $this->presupuesto->nombrecliente;
      $pedido->observaciones = $this->presupuesto->observaciones;
      $pedido->provincia = $this->presupuesto->provincia;
      $pedido->total = $this->presupuesto->total;
      $pedido->totaliva = $this->presupuesto->totaliva;
      $pedido->numero2 = $this->presupuesto->numero2;
      $pedido->irpf = $this->presupuesto->irpf;
      $pedido->porcomision = $this->presupuesto->porcomision;
      $pedido->totalirpf = $this->presupuesto->totalirpf;
      $pedido->totalrecargo = $this->presupuesto->totalrecargo;

      /**
       * Obtenemos el ejercicio para la fecha de hoy (puede que no sea
       * el mismo ejercicio que el del presupuesto, por ejemplo si hemos cambiado de año).
       */
      $eje0 = $this->ejercicio->get_by_fecha($pedido->fecha);
      $pedido->codejercicio = $eje0->codejercicio;

      $regularizacion = new regularizacion_iva();

      if (!$eje0->abierto())
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if ($regularizacion->get_fecha_inside($pedido->fecha))
      {
         $this->new_error_msg("El IVA de ese periodo ya ha sido regularizado. No se pueden añadir más " . FS_PEDIDOS . " en esa fecha.");
      }
      else if ($pedido->save())
      {
         $continuar = TRUE;
         foreach ($this->presupuesto->get_lineas() as $l)
         {
            $n = new linea_pedido_cliente();
            $n->idlineapresupuesto = $l->idlinea;
            $n->idpresupuesto = $l->idpresupuesto;
            $n->idpedido = $pedido->idpedido;
            $n->cantidad = $l->cantidad;
            $n->codimpuesto = $l->codimpuesto;
            $n->descripcion = $l->descripcion;
            $n->dtopor = $l->dtopor;
            $n->irpf = $l->irpf;
            $n->iva = $l->iva;
            $n->pvpsindto = $l->pvpsindto;
            $n->pvptotal = $l->pvptotal;
            $n->pvpunitario = $l->pvpunitario;
            $n->recargo = $l->recargo;
            $n->referencia = $l->referencia;
            if (!$n->save())
            {
               $continuar = FALSE;
               $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
               break;
            }
         }

         if ($continuar)
         {
            $this->presupuesto->idpedido = $pedido->idpedido;
            
            if( $this->presupuesto->save() )
            {
               $this->new_message("<a href='" . $pedido->url() . "'>" . ucfirst(FS_PEDIDO) . '</a> generado correctamente.');
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el " . FS_PRESUPUESTO . " con el nuevo " . FS_PEDIDO . "!");
               if( $pedido->delete() )
               {
                  $this->new_error_msg("El " . FS_PEDIDO . " se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el " . FS_PEDIDO . "!");
            }
         }
         else
         {
            if( $pedido->delete() )
            {
               $this->new_error_msg("El " . FS_PEDIDO . " se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar el " . FS_PEDIDO . "!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el " . FS_PEDIDO . "!");
   }
}
