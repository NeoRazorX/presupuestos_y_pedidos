<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017  Carlos Garcia Gomez       neorazorx@gmail.com
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   shawe.ewahs@gmail.com
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

require_model('albaran_proveedor.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('fabricante.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('linea_pedido_proveedor.php');
require_model('pedido_proveedor.php');
require_model('proveedor.php');
require_model('serie.php');

class compras_pedido extends fs_controller
{
   public $agente;
   public $allow_delete;
   public $almacen;
   public $divisa;
   public $ejercicio;
   public $fabricante;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $nuevo_pedido_url;
   public $pedido;
   public $proveedor;
   public $proveedor_s;
   public $serie;
   public $versiones;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, ucfirst(FS_PEDIDO), 'compras', FALSE, FALSE);
   }

   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->ppage = $this->page->get('compras_pedidos');
      $this->agente = FALSE;
      
      $this->almacen = new almacen();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->fabricante = new fabricante();
      $this->familia = new familia();
      $this->forma_pago = new forma_pago();
      $this->impuesto = new impuesto();
      $this->nuevo_pedido_url = FALSE;
      $pedido = new pedido_proveedor();
      $this->pedido = FALSE;
      $this->proveedor = new proveedor();
      $this->proveedor_s = FALSE;
      $this->serie = new serie();
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_compra,
       * necesario para poder añadir líneas.
       */
      if ($this->user->have_access_to('nueva_compra', FALSE))
      {
         $nuevopedp = $this->page->get('nueva_compra');
         if($nuevopedp)
         {
            $this->nuevo_pedido_url = $nuevopedp->url();
         }
      }
      
      /**
       * Primero ejecutamos la función del cron para desbloquear los
       * pedidos de albaranes eliminados y devolverlos al estado original.
       */
      $pedido->cron_job();
      
      if( isset($_POST['idpedido']) )
      {
         $this->pedido = $pedido->get($_POST['idpedido']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->pedido = $pedido->get($_GET['id']);
      }

      if($this->pedido)
      {
         $this->page->title = $this->pedido->codigo;

         /// cargamos el agente
         if( !is_null($this->pedido->codagente) )
         {
            $agente = new agente();
            $this->agente = $agente->get($this->pedido->codagente);
         }

         /// cargamos el proveedor
         $this->proveedor_s = $this->proveedor->get($this->pedido->codproveedor);

         /// comprobamos el pedido
         $this->pedido->full_test();
         
         if( isset($_POST['aprobar']) AND isset($_POST['petid']) AND is_null($this->pedido->idalbaran) )
         {
            if( $this->duplicated_petition($_POST['petid']) )
            {
               $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
            }
            else
               $this->generar_albaran();
         }
         else if( isset($_GET['desbloquear']) )
         {
            $this->pedido->editable = TRUE;
            $this->pedido->save();
         }
         else if( isset($_GET['nversion']) )
         {
            $this->nueva_version();
         }
         else if( isset($_GET['nversionok']) )
         {
            $this->new_message('Esta es la nueva versión del '.FS_PEDIDO.'.');
         }
         
         $this->versiones = $this->pedido->get_versiones();
      }
      else
      {
         $this->new_error_msg("¡" . ucfirst(FS_PEDIDO) . " de proveedor no encontrado!", 'error', FALSE, FALSE);
      }
   }
   
   private function nueva_version()
   {
      $pedi = clone $this->pedido;
      $pedi->idpedido = NULL;
      $pedi->idalbaran = NULL;
      $pedi->fecha = $this->today();
      $pedi->hora = $this->hour();
      $pedi->editable = TRUE;
      $pedi->numdocs = 0;
      
      $pedi->idoriginal = $this->pedido->idpedido;
      if($this->pedido->idoriginal)
      {
         $pedi->idoriginal = $this->pedido->idoriginal;
      }
      
      /// enlazamos con el ejercicio correcto
      $ejercicio = $this->ejercicio->get_by_fecha($pedi->fecha);
      if($ejercicio)
      {
         $pedi->codejercicio = $ejercicio->codejercicio;
      }
      
      if( $pedi->save() )
      {
         /// también copiamos las líneas del presupuesto
         foreach($this->pedido->get_lineas() as $linea)
         {
            $newl = clone $linea;
            $newl->idlinea = NULL;
            $newl->idpedido = $pedi->idpedido;
            $newl->save();
         }
         
         $this->new_message('<a href="' . $pedi->url() . '">Documento</a> de ' . FS_PEDIDO . ' copiado correctamente.');
         header('Location: '.$pedi->url().'&nversionok=TRUE');
      }
      else
      {
         $this->new_error_msg('Error al copiar el documento.');
      }
   }

   public function url()
   {
      if (!isset($this->pedido))
      {
         return parent::url();
      }
      else if ($this->pedido)
      {
         return $this->pedido->url();
      }
      else
         return $this->page->url();
   }

   private function modificar()
   {
      $this->pedido->observaciones = $_POST['observaciones'];
      $this->pedido->numproveedor = $_POST['numproveedor'];
      
      /// ¿El pedido es editable o ya ha sido aprobado?
      if( is_null($this->pedido->idalbaran) )
      {
         $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha'], FALSE);
         if($eje0)
         {
            $this->pedido->fecha = $_POST['fecha'];
            $this->pedido->hora = $_POST['hora'];
            
            if($this->pedido->codejercicio != $eje0->codejercicio)
            {
               $this->pedido->codejercicio = $eje0->codejercicio;
               $this->pedido->new_codigo();
            }
         }
         else
         {
            $this->new_error_msg('Ningún ejercicio encontrado.');
         }
         
         /// ¿cambiamos el proveedor?
         if($_POST['proveedor'] != $this->pedido->codproveedor)
         {
            $proveedor = $this->proveedor->get($_POST['proveedor']);
            if($proveedor)
            {
               $this->pedido->codproveedor = $proveedor->codproveedor;
               $this->pedido->nombre = $proveedor->razonsocial;
               $this->pedido->cifnif = $proveedor->cifnif;
            }
            else
            {
               $this->pedido->codproveedor = NULL;
               $this->pedido->nombre = $_POST['nombre'];
               $this->pedido->cifnif = $_POST['cifnif'];
            }
         }
         else
         {
            $this->pedido->nombre = $_POST['nombre'];
            $this->pedido->cifnif = $_POST['cifnif'];
            $proveedor = $this->proveedor->get($this->pedido->codproveedor);
         }

         $serie = $this->serie->get($this->pedido->codserie);

         /// ¿cambiamos la serie?
         if($_POST['serie'] != $this->pedido->codserie)
         {
            $serie2 = $this->serie->get($_POST['serie']);
            if($serie2)
            {
               $this->pedido->codserie = $serie2->codserie;
               $this->pedido->new_codigo();

               $serie = $serie2;
            }
         }
         
         $this->pedido->codalmacen = $_POST['almacen'];
         $this->pedido->codpago = $_POST['forma_pago'];
         
         /// ¿Cambiamos la divisa?
         if($_POST['divisa'] != $this->pedido->coddivisa)
         {
            $divisa = $this->divisa->get($_POST['divisa']);
            if($divisa)
            {
               $this->pedido->coddivisa = $divisa->coddivisa;
               $this->pedido->tasaconv = $divisa->tasaconv_compra;
            }
         }
         else if($_POST['tasaconv'] != '')
         {
            $this->pedido->tasaconv = floatval($_POST['tasaconv']);
         }
         
         if( isset($_POST['numlineas']) )
         {
            $numlineas = intval($_POST['numlineas']);

            $this->pedido->neto = 0;
            $this->pedido->totaliva = 0;
            $this->pedido->totalirpf = 0;
            $this->pedido->totalrecargo = 0;
            $this->pedido->irpf = 0;
            
            $lineas = $this->pedido->get_lineas();
            $articulo = new articulo();

            /// eliminamos las líneas que no encontremos en el $_POST
            foreach($lineas as $l)
            {
               $encontrada = FALSE;
               for($num = 0; $num <= $numlineas; $num++)
               {
                  if( isset($_POST['idlinea_' . $num]) )
                  {
                     if($l->idlinea == intval($_POST['idlinea_' . $num]))
                     {
                        $encontrada = TRUE;
                        break;
                     }
                  }
               }
               if(!$encontrada)
               {
                  if( !$l->delete() )
                  {
                     $this->new_error_msg("¡Imposible eliminar la línea del artículo " . $l->referencia . "!");
                  }
               }
            }
            
            $regimeniva = 'general';
            if($proveedor)
            {
               $regimeniva = $proveedor->regimeniva;
            }
            
            /// modificamos y/o añadimos las demás líneas
            for($num = 0; $num <= $numlineas; $num++)
            {
               $encontrada = FALSE;
               if( isset($_POST['idlinea_' . $num]) )
               {
                  foreach($lineas as $k => $value)
                  {
                     /// modificamos la línea
                     if($value->idlinea == intval($_POST['idlinea_' . $num]))
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
                        if(!$serie->siniva AND $regimeniva != 'Exento')
                        {
                           $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                           if($imp0)
                           {
                              $lineas[$k]->codimpuesto = $imp0->codimpuesto;
                           }

                           $lineas[$k]->iva = floatval($_POST['iva_' . $num]);
                           $lineas[$k]->recargo = floatval($_POST['recargo_' . $num]);
                        }

                        if( $lineas[$k]->save() )
                        {
                           $this->pedido->neto += $value->pvptotal;
                           $this->pedido->totaliva += $value->pvptotal * $value->iva / 100;
                           $this->pedido->totalirpf += $value->pvptotal * $value->irpf / 100;
                           $this->pedido->totalrecargo += $value->pvptotal * $value->recargo / 100;
                           
                           if($value->irpf > $this->pedido->irpf)
                           {
                              $this->pedido->irpf = $value->irpf;
                           }
                        }
                        else
                           $this->new_error_msg("¡Imposible modificar la línea del artículo " . $value->referencia . "!");
                        
                        break;
                     }
                  }

                  /// añadimos la línea
                  if( !$encontrada AND intval($_POST['idlinea_' . $num]) == -1 AND isset($_POST['referencia_' . $num]) )
                  {
                     $linea = new linea_pedido_proveedor();
                     $linea->idpedido = $this->pedido->idpedido;
                     $linea->descripcion = $_POST['desc_' . $num];
                     
                     if(!$serie->siniva AND $regimeniva != 'Exento')
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_' . $num]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                        }
                        
                        $linea->iva = floatval($_POST['iva_' . $num]);
                        $linea->recargo = floatval($_POST['recargo_' . $num]);
                     }
                     
                     $linea->irpf = floatval($_POST['irpf_' . $num]);
                     $linea->cantidad = floatval($_POST['cantidad_' . $num]);
                     $linea->pvpunitario = floatval($_POST['pvp_' . $num]);
                     $linea->dtopor = floatval($_POST['dto_' . $num]);
                     $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                     $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor) / 100);
                     
                     $art0 = $articulo->get($_POST['referencia_' . $num]);
                     if($art0)
                     {
                        $linea->referencia = $art0->referencia;
                        if($_POST['codcombinacion_' . $num])
                        {
                           $linea->codcombinacion = $_POST['codcombinacion_' . $num];
                        }
                     }
                     
                     if( $linea->save() )
                     {
                        $this->pedido->neto += $linea->pvptotal;
                        $this->pedido->totaliva += $linea->pvptotal * $linea->iva / 100;
                        $this->pedido->totalirpf += $linea->pvptotal * $linea->irpf / 100;
                        $this->pedido->totalrecargo += $linea->pvptotal * $linea->recargo / 100;
                        
                        if($linea->irpf > $this->pedido->irpf)
                        {
                           $this->pedido->irpf = $linea->irpf;
                        }
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar la línea del artículo " . $linea->referencia . "!");
                  }
               }
            }

            /// redondeamos
            $this->pedido->neto = round($this->pedido->neto, FS_NF0);
            $this->pedido->totaliva = round($this->pedido->totaliva, FS_NF0);
            $this->pedido->totalirpf = round($this->pedido->totalirpf, FS_NF0);
            $this->pedido->totalrecargo = round($this->pedido->totalrecargo, FS_NF0);
            $this->pedido->total = $this->pedido->neto + $this->pedido->totaliva - $this->pedido->totalirpf + $this->pedido->totalrecargo;

            if( abs(floatval($_POST['atotal']) - $this->pedido->total) >= .02 )
            {
               $this->new_error_msg("El total difiere entre el controlador y la vista (" . $this->pedido->total .
                       " frente a " . $_POST['atotal'] . "). Debes informar del error.");
            }
         }
      }

      if( $this->pedido->save() )
      {
         $this->new_message(ucfirst(FS_PEDIDO) . " modificado correctamente.");
         $this->new_change(ucfirst(FS_PEDIDO) . ' Proveedor ' . $this->pedido->codigo, $this->pedido->url());
      }
      else
         $this->new_error_msg("¡Imposible modificar el " . FS_PEDIDO . "!");
   }

   private function generar_albaran()
   {
      $albaran = new albaran_proveedor();
      $albaran->cifnif = $this->pedido->cifnif;
      $albaran->codagente = $this->pedido->codagente;
      $albaran->codalmacen = $this->pedido->codalmacen;
      $albaran->codproveedor = $this->pedido->codproveedor;
      $albaran->coddivisa = $this->pedido->coddivisa;
      $albaran->tasaconv = $this->pedido->tasaconv;
      $albaran->codpago = $this->pedido->codpago;
      $albaran->codserie = $this->pedido->codserie;
      $albaran->neto = $this->pedido->neto;
      $albaran->nombre = $this->pedido->nombre;
      $albaran->observaciones = $this->pedido->observaciones;
      $albaran->total = $this->pedido->total;
      $albaran->totaliva = $this->pedido->totaliva;
      $albaran->numproveedor = $this->pedido->numproveedor;
      $albaran->irpf = $this->pedido->irpf;
      $albaran->totalirpf = $this->pedido->totalirpf;
      $albaran->totalrecargo = $this->pedido->totalrecargo;
      
      if( is_null($albaran->codagente) )
      {
         $albaran->codagente = $this->user->codagente;
      }
      
      /**
       * Obtenemos el ejercicio para la fecha seleccionada.
       */
      $eje0 = $this->ejercicio->get_by_fecha($_POST['aprobar'], FALSE);
      if($eje0)
      {
         $albaran->fecha = $_POST['aprobar'];
         $albaran->codejercicio = $eje0->codejercicio;
      }
      
      if(!$eje0)
      {
         $this->new_error_msg("Ejercicio no encontrado o está cerrado.");
      }
      else if( !$eje0->abierto() )
      {
         $this->new_error_msg("El ejercicio está cerrado.");
      }
      else if( $albaran->save() )
      {
         $art0 = new articulo();
         $continuar = TRUE;
         $trazabilidad = FALSE;
         
         foreach($this->pedido->get_lineas() as $l)
         {
            $n = new linea_albaran_proveedor();
            $n->idlineapedido = $l->idlinea;
            $n->idpedido = $l->idpedido;
            $n->idalbaran = $albaran->idalbaran;
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
            $n->codcombinacion = $l->codcombinacion;

            if( $n->save() )
            {
               /// añadimos al stock
               if( $n->referencia AND isset($_POST['stock']) )
               {
                  $articulo = $art0->get($n->referencia);
                  if($articulo)
                  {
                     $articulo->sum_stock($albaran->codalmacen, $l->cantidad, isset($_POST['costemedio']), $l->codcombinacion);
                     if($articulo->trazabilidad)
                     {
                        $trazabilidad = TRUE;
                     }
                  }
               }
            }
            else
            {
               $this->new_error_msg("¡Imposible guardar la línea el artículo " . $n->referencia . "! ");
               $continuar = FALSE;
            }
         }

         if($continuar)
         {
            $this->pedido->idalbaran = $albaran->idalbaran;
            $this->pedido->editable = FALSE;
            
            if( $this->pedido->save() )
            {
               $this->new_message("<a href='" . $albaran->url() . "'>" . ucfirst(FS_ALBARAN) . '</a> generado correctamente.');
               if($trazabilidad)
               {
                  header('Location: index.php?page=compras_trazabilidad&doc=albaran&id='.$albaran->idalbaran);
               }
            }
            else
            {
               $this->new_error_msg("¡Imposible vincular el ".FS_PEDIDO." con el nuevo " . FS_ALBARAN . "!");
               if( $albaran->delete() )
               {
                  $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
            }
         }
         else
         {
            if( $albaran->delete() )
            {
               $this->new_error_msg("El " . FS_ALBARAN . " se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar el " . FS_ALBARAN . "!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el " . FS_ALBARAN . "!");
   }
}
