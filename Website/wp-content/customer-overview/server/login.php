<?php

include_once("common.php");

add_action( 'register_form', 'myplugin_register_form' );
function myplugin_register_form() {
    
    if(isset($_GET["apiKey"])){
        $apiKey = $_GET["apiKey"];
    }
    else if(isset($_POST['apiKey'])) {
        $apiKey = $_POST['apiKey'];
    }
    
    if(!isset($apiKey) && isAdmin()) {
        $apiKey = getApiKey();
    }

    $companyName = '';

    if(isset($apiKey)){
        $curl = curl_init();
        $url = 'https://handle-data.integrations.online-it-support.dk/handleData.ashx';

        $postFields = null;
        $postFields = array("getCustomerName" => '1', 'ApiKey' => $apiKey);

            curl_setopt_array($curl, array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_BINARYTRANSFER => true,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_VERBOSE => true,
              //CURLOPT_STDERR => $filep,
              CURLOPT_POSTFIELDS => $postFields,
              CURLOPT_HTTPHEADER => array(
                'Content-Type: multipart/form-data'
              )
            ));


        $companyName = curl_exec($curl);
    }


    $first_name = ( ! empty( $_POST['first_name'] ) ) ? trim( $_POST['first_name'] ) : '';
    $last_name = ( ! empty( $_POST['last_name'] ) ) ? trim( $_POST['last_name'] ) : '';

        ?>

        <style>
            
            #login{
                max-width:500px !important;
            }
            
            #registerform > p:first-child {
                display:none;
            }
            <? if(!isset($apiKey)){ ?>
                #registerform p{
                    display: none;
                }
            <? }
            else { ?>
                .infoWhenNoApiKey{
                    display: none;
                }
            <? } ?>

        </style>
        
        <p>
            <label for="first_name"><?php _e( 'Fornavn') ?></label>
                <input type="text" name="first_name" id="first_name" class="input" value="<?php echo esc_attr( wp_unslash( $first_name ) ); ?>" size="25" />
        </p>

        <p>
            <label for="last_name"><?php _e( 'Efternavn') ?></label>
                <input type="text" name="last_name" id="last_name" class="input" value="<?php echo esc_attr( wp_unslash( $last_name ) ); ?>" size="25" />
        </p>
        <p>
            <label for="company"><?php _e( 'Firmanavn') ?></label>
                <input disabled="disabled" type="text" value="<? echo $companyName ?>"  name="company" id="company" class="input" size="25" />
        </p>
        <p>
            <input type="checkbox" name="admin" id="admin" />
            <label for="admin"><?php _e( 'Administrator') ?></label>
                
                <br/>Som administrator er der adgang til at redigere andre brugere og deres rettigheder<br/><br/>
        </p>
         <p style="display:none;">
            <label for="apiKey"><?php _e( 'Api Key') ?></label>
            <input type="text" name="apiKey" id="apiKey" class="input" value="<?php echo $apiKey ?>" size="25" />
        </p>

        <h3 class="infoWhenNoApiKey">
            Kontakt Online IT Support på <a href="tel:22982212">22 98 22 12</a> eller <a href="mailto:kontakt@online-it-support.dk">kontakt@online-it-support.dk</a> for at få adgang til det bedste bogholder / revisor dashboard på markedet
        </h3>

        <script>
            
            function init(){
                document.addEventListener('keyup', function (event) {
	                // If the clicked element doesn't have the right selector, bail
	                if (!event.target.matches('#user_email')) return;

	                // Log the clicked element in the console
	                 document.getElementById('user_login').value = event.target.value;

                }, false);
            }
            
            window.setTimeout(init, 100);

        </script>

        <?php
    }

    //2. Add validation. In this case, we make sure first_name and last_name is required.
    add_filter( 'registration_errors', 'myplugin_registration_errors', 10, 3 );
    function myplugin_registration_errors( $errors, $sanitized_user_login, $user_email ) {

        if ( empty( $_POST['first_name'] ) || ! empty( $_POST['first_name'] ) && trim( $_POST['first_name'] ) == '' ) {
            $errors->add( 'first_name_error', __( '<strong>ERROR</strong>: You must include a first name.') );
        }
        if ( empty( $_POST['last_name'] ) || ! empty( $_POST['last_name'] ) && trim( $_POST['last_name'] ) == '' ) {
            $errors->add( 'last_name_error', __( '<strong>ERROR</strong>: You must include a last name.') );
        }

        return $errors;
    }

    //3. Finally, save our extra registration user meta.
    add_action( 'user_register', 'myplugin_user_register' );
    function myplugin_user_register( $user_id ) {
        if(!isset($_POST['first_name'])){
            return;
        }    

        $first_name = trim( $_POST['first_name'] );
        $last_name = trim( $_POST['last_name'] );

        if ( ! empty( $_POST['first_name'] ) ) {
            update_user_meta( $user_id, 'first_name',  $first_name);
            update_user_meta( $user_id, 'last_name', $last_name);
        }

        $apiKey = $_POST['apiKey'];
        update_user_meta( $user_id, 'apiKey', $apiKey);

        if(isset($_POST['admin']) && trim( $_POST['admin'] ) == "on"){
            $wp_user_object = new WP_User($user_id);
            $wp_user_object->set_role('coadmin');
        }

        $full_name = trim( $first_name . ' ' . $last_name );
        
        $userdata = array(
            'ID' => $user_id,
            'display_name' => $full_name,
        );

        wp_update_user( $userdata );
    }

    add_filter( 'login_redirect', 'custom_login_redirect', 10, 3 );
    function custom_login_redirect( $redirect_to, $request, $user ) {
        if ( is_a ( $user , 'WP_User' ) && $user->exists() ) {
            if ( !current_user_can( 'administrator' ) ) {
                $redirect_to = "/";
            }
        }

        return $redirect_to;
    }

    
    function auto_login($user_id) {
		//login
		wp_set_current_user($user_id, "admin");
		wp_set_auth_cookie($user_id);
		do_action('wp_login', $user_login);
		wp_redirect( home_url() );
    }

    if($_GET["demo"] == "login" || $_GET["demo"] == "admin"){
	    auto_login(11);
    }
    else if(isset($_GET["demo"])){
	    auto_login(10);
    }


    