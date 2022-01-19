<?php

if(!class_exists('WP_List_Table')){
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class FileDownloadTable extends WP_List_Table {

   /**
    * Constructor, we override the parent to pass our own arguments
   * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
   */
   function __construct() {
      parent::__construct(array(
         'singular'  => 'wp_list_text_link', //Singular label
         'plural'    => 'wp_list_test_links', //plural label, also this well be one of the table css class
         'ajax'      => false //We won't support Ajax for this table
      ));
   }

   function extra_tablenav( $which ) {
      if ( $which == "top" ){
      }
      if ( $which == "bottom" ){
      }
   }

   public function get_columns() {
      return $columns= array(
         'col_id'          =>'ID',
         'col_link_name'   =>'ファイルパス',
         'col_date'        =>'日付'
      );
   }

   public function get_sortable_columns() {
      return $sortable = array(
         'col_id'          =>'id',
         'col_link_name'   =>'file_path',
         'col_date'        =>'created_at'
      );
   }

   function prepare_items() {
      global $wpdb, $_wp_column_headers, $table_prefix;
      $tblname = 'download_file';
      $wp_track_table = $table_prefix . "$tblname";
      $screen = get_current_screen();

      $query = "SELECT * FROM $wp_track_table";

      $orderby = !empty($_GET["orderby"]) ? trim($_GET["orderby"]) : 'ASC';
      $order = !empty($_GET["order"]) ? trim($_GET["order"]) : '';
      if(!empty($orderby) & !empty($order)){ 
         $query.=' ORDER BY '.$orderby.' '.$order; 
      }

      $totalitems = $wpdb->query($query);
      $perpage = 10;
      $paged = !empty($_GET["paged"]) ? trim($_GET["paged"]) : '';
      if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ 
         $paged=1;
      }
      
      $totalpages = ceil($totalitems/$perpage);
      if(!empty($paged) && !empty($perpage)){ 
         $offset=($paged-1)*$perpage; 
         $query.=' LIMIT '.(int)$offset.','.(int)$perpage; 
      } 

      $this->set_pagination_args(array(
         "total_items" => $totalitems,
         "total_pages" => $totalpages,
         "per_page" => $perpage,
      ));

      $columns = $this->get_columns();
      $_wp_column_headers[$screen->id]=$columns;

      $this->items = $wpdb->get_results($query);
   }

   /**
    * Display the rows of records in the table
    * @return string, echo the markup of the rows
    */
   function display_rows() {
      $records = $this->items;

      list( $columns, $hidden, $sortable ) = [
         $this->get_columns(),
         [],
         $this->get_sortable_columns(),
         $this->get_primary_column_name(),
      ];
      if(!empty($records)){
         foreach($records as $rec){

            echo '<tr id="record_'.$rec->id.'">';
            foreach ( $columns as $column_name => $column_display_name ) {

               $class = "class='$column_name column-$column_name'";
               $style = "";
               if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
               $attributes = $class . $style;

               switch ( $column_name ) {
                  case "col_id":  
                     echo '<td '.$attributes.'>'.stripslashes($rec->id).'</td>';   
                  break;
                  case "col_link_name":
                     $filename = preg_replace('/^.+\\\\/', '', $rec->file_path);
                     echo '<td '.$attributes.'><a href="' . get_home_url() . DIRECTORY_SEPARATOR . $rec->file_path . '" target="_blank">'.$filename.'</a></td>'; 
                  break;
                  case "col_date": 
                     echo '<td '.$attributes.'>'.stripslashes($rec->created_at).'</td>'; 
                  break;
               }
            }

            echo'</tr>';
         }
      }
   }

   public function print_column_headers($with_id = true) {
      $columns = $this->get_columns();

      echo '<tr class="pdflist-header">';
      foreach ( $columns as $column_name => $column_display_name ) {
         $attributes = "class='$column_name column-$column_name'";
         echo '<td '.$attributes.'>'.stripslashes($column_display_name).'</td>';   
      }
      echo'</tr>';
   }
   
}
