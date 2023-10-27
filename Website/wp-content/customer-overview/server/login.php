<?php

add_action( 'register_form', 'myplugin_register_form' );
function myplugin_register_form() {
    
    $apiKey = $_GET["apiKey"];

    if(!isset($companyId)){
        $companyId = $_POST['companyId'];
    }
    $company = null;

    if(isset($companyId)){
        $company = Company::getCompanyById($companyId);
    }

    $noCompany = $company == null;
    $first_name = ( ! empty( $_POST['first_name'] ) ) ? trim( $_POST['first_name'] ) : '';
    $last_name = ( ! empty( $_POST['last_name'] ) ) ? trim( $_POST['last_name'] ) : '';

        ?>

        <style>
            #registerform > p:first-child {
                display:none;
            }
            <? if($noCompany){ ?>
                #login{
                    display: none;
                }
            <? } ?>
        </style>
<? 

//  Hide normal register form when no customer
if(!isset($apiKey)) {
?>
            <script>
                location.href = "/?demo=1";
            </script>
<? }
?>
        
        <p>
            <label for="first_name"><?php _e( 'Fornavn') ?><br />
                <input type="text" name="first_name" id="first_name" class="input" value="<?php echo esc_attr( wp_unslash( $first_name ) ); ?>" size="25" /></label>
        </p>

        <p>
            <label for="last_name"><?php _e( 'Efternavn') ?><br />
                <input type="text" name="last_name" id="last_name" class="input" value="<?php echo esc_attr( wp_unslash( $last_name ) ); ?>" size="25" /></label>
        </p>
        <p>
            <label for="company"><?php _e( 'Firmanavn') ?><br />
                <input type="text"  name="company" id="company" class="input" size="25" /></label>
        </p>
        <p>
            <label for="companyAdmin"><?php _e( 'Administrator') ?><br />
                <input type="checkbox" name="companyAdmin" id="companyAdmin" /></label>
                <br/>Som administrator kan man redigere andre brugers adgang<br/><br/>
        </p>
         <p style="display:none;">
            <label for="companyId"><?php _e( 'Firma Id') ?><br />
            <input type="text" name="apiKey" id="apiKey" class="input" value="<?php echo $apiKey ?>" size="25" /></label>
        </p>

        <script>
            
            function init(){
                document.addEventListener('keyup', function (event) {
	                // If the clicked element doesn't have the right selector, bail
	                if (!event.target.matches('#user_email')) return;

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

        if ( ! empty( $_POST['first_name'] ) ) {
            update_user_meta( $user_id, 'first_name', trim( $_POST['first_name'] ) );
            update_user_meta( $user_id, 'last_name', trim( $_POST['last_name'] ) );
        }

        $apiKey = $_POST['apiKey'];
        update_user_meta( $user_id, 'apiKey', $apiKey);
        update_user_meta( $user_id, 'companyAdmin', trim( $_POST['companyAdmin'] ) );
    }

    add_action( 'wp_login', 'wpse_9326315_format_user_display_name_on_login' );

    function wpse_9326315_format_user_display_name_on_login( $username ) {
        $user = get_user_by( 'login', $username );

        $first_name = get_user_meta( $user->ID, 'first_name', true );
        $last_name = get_user_meta( $user->ID, 'last_name', true );

        $full_name = trim( $first_name . ' ' . $last_name );

        if ( ! empty( $full_name ) && ( $user->data->display_name != $full_name ) ) {
            $userdata = array(
                'ID' => $user->ID,
                'display_name' => $full_name,
            );

            wp_update_user( $userdata );
        }
    }


    