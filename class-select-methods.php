<?php
class SitiWeb_SelectCourier_methods{

    private $method_field_name;

    public function __construct(){
        $this->method_field_name = 'selectcourier_methods_settings';
    }

    public function get_methods_settings(){
        return get_option($this->method_field_name, []);
    }

    public function delete_methods_settings(){
        return delete_option($this->method_field_name);
    }

    public function get_method_setting($method){
        $methods = $this->get_methods_settings();
       
        $method_keycode = sanitize_title($method->id);
        if($methods && isset($methods[$method_keycode])){
            return $methods[$method_keycode];
        }
        return [];
    }

    public function get_method_image($method){
        $method_result = $this->get_method_setting($method);

        if ($method_result){
            if (isset($method_result['courier_logo'])){
                return $method_result['courier_logo'];
            }
            
        }
        return false;
    }

    public function set_method_in_db($method){
        $methods = $this->get_methods_settings();
        $method_keycode = sanitize_title('select_courier_' . $method['service_keycode']);
        if (!in_array($method_keycode, $methods)){

            $methods[$method_keycode] = [
                'service_keycode' => $method['service_keycode'],
                'courier_logo' => $method['courier_logo'],
            ];
            update_option($this->method_field_name, $methods);
        }
        else{
            if ($methods[$method] != $method){
                $methods[$method_keycode] = $method;
                update_option($this->method_field_name, $methods);
            }
        }


    }
}