<?php
/**
 * Google Sheet Categories Controller
 * 
 * */

class WCGS_Categories {
    
    function __construct() {
        
        $this->map = ['Name'   => 0,
                    'Description' => 1,
                    'ID'              => 2,
                    'ParentID'                => 3,
                    'Sync'              => 4];
                    
        // $this->categories = $categories_data;
        $this->rowRef = array();
    }
    
    function get_value($column, $row) {
        
        return isset($row[$this->map[$column]]) ? $row[$this->map[$column]] : '';
    }
    
    // function name($row) {
    //     return isset($row[$this->map['Name']]) ? $row[$this->map['Name']] : '';
    // }
    // function slug($row) {
    //     return isset($row[$this->map['slug']]) ? $row[$this->map['slug']] : '';
    // }
    // function id($row) {
    //     return isset($row[$this->map['ID']]) ? $row[$this->map['ID']] : '';
    // }
    // function sync($row) {
    //     return isset($row[$this->map['Sync']]) ? $row[$this->map['Sync']] : '';
    // }
    
    function get_data(){
        
        $gs = new GoogleSheet_API();
        $range = 'categories';
        $rows = $gs->get_sheet_rows($range);
        
        unset($rows[0]);    // Skip heading row
        $parse_Rows = array();
        $rowRef = array();
        $rowIndex = 2;
        foreach($rows as $row){
            
            $id   = $this->get_value('ID', $row);
            $name = $this->get_value('Name', $row);
            $parent = $this->get_value('ParentID', $row);
            $desc = $this->get_value('Description', $row);
            $sync = $this->get_value('Sync', $row);
            
            if( $sync == 1 ) {
                $rowIndex++;
                continue;
            }
            
            $batch_data = array();
            if( $id != '' ) {
                $parse_Rows['update'][] = ['id'=>$id, 'name'=>$name, 'description'=>$desc, 'parent'=>$parent];   
                $rowRef[$id] = $rowIndex;
            }else{
                $parse_Rows['create'][] = ['name'=>$name, 'description'=>$desc, 'parent'=>$parent];
                $rowRef[$name] = $rowIndex;
            }
            
            $rowIndex++;
            
        }
        
        wcgs_pa($parse_Rows);
        $this->rowRef = $rowRef;
        return $parse_Rows;
    }
}