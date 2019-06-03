<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$errors = array();
$fields = array("login" => array("email", "password"),
           "sign_up" => array("email", "password", "restaurant_name", "first_name", "last_name")
    );


$mode = isset($_GET["login"]) && $_GET["login"] ? "login" : "sign_up";

// Login or Sign Up
    if ( isset( $_POST["submit"])) {
        if (! (current_user_can('manage_options') && isset($_POST[ '_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'glf-mor-auth' )))
            die( 'Access restricted, security check failed!' );

        $errors = glf_mor_validate_form($fields[$mode]);
        if (empty($errors)) {
            $respone_body = glf_mor_remote_call($this->api_url, $mode);

            if (isset($respone_body->errorDescription)) {
                $errors["form"] = $respone_body->errorDescription;
            } else {
                if ($mode != 'login') {
                    $respone_body = glf_mor_remote_call($this->api_url, 'login');
                    if (isset($respone_body->errorDescription)) {
                        die("Something went wrong: $respone_body->errorDescription");
                    }
                }

                $this->save_user_data(array(
                    'user' => $respone_body->user,
                    'auth_domain' => $respone_body->domain,
                    'auth_token' => $respone_body->token
                ));
                echo "<script>window.location.href = 'admin.php?page=glf-admin'</script>";
                die();
            }
        }
    }


if (! isset($_POST['email'])) {
    $_POST['email'] = wp_get_current_user()->user_email;
}

if (! isset($_POST['first_name'])) {
    $_POST['first_name'] = wp_get_current_user()->user_firstname;
}

if (! isset($_POST['last_name'])) {
    $_POST['last_name'] = wp_get_current_user()->user_lastname;
}

function glf_mor_validate_form($fields) {
    $errors = array();
    foreach ($fields as $field) {
         if (!isset($_POST[$field]) || empty($_POST[$field])) {
             $errors[$field] = "Please fill in the " . glf_mor_field_to_label($field);
         } elseif ($field == "email" && !is_email($_POST[$field])) {
             $errors[$field] = "The Email Address you inserted is invalid!";
         }
     }
     return $errors;
}

function glf_mor_display_error($field, $errors) {
    if (isset($errors[$field])) {
        ?><div class="error-message"><?= $errors[$field];?></div><?php
    }
}

function glf_mor_get_field_value($field) {
    if (isset($_POST[$field])) {
        $field_value = sanitize_text_field($_POST[$field]);
        $field_value = stripslashes($field_value);
        return esc_html($field_value);
    } else {
        return glf_mor_get_default_field_value($field);
    }
}

function glf_mor_get_default_field_value($field) {
    $current_user = wp_get_current_user();
    $default_values = array("email" => $current_user->user_email,
        "first_name" => $current_user->user_firstname,
        "last_name" => $current_user->user_lastname);

    if (isset($default_values[$field])) {
        return $default_values[$field];
    }  else {
        return '';
    }
}

function glf_mor_field_to_label($field) {
    switch ($field) {
        case 'email':
            return __('Email', 'menu-ordering-reservations');

        case 'password':
            return __('Password', 'menu-ordering-reservations');

        case 'restaurant_name':
            return __('Restaurant name', 'menu-ordering-reservations');

        case 'first_name':
            return __('First name', 'menu-ordering-reservations');

        case 'last_name':
            return __('Last name', 'menu-ordering-reservations');

        default:
            return ucwords(str_replace("_", " ", $field));
    }

}

function glf_mor_display_form_field($field, $errors) {
    ?>
    <tr>
        <th><?= glf_mor_field_to_label($field);?></th>
        <td>
            <input class="glf-input" <?php echo $field=='password' ? 'type="password" autocomplete="off"' : 'type="text"';?>"  name="<?= $field;?>" value="<?= glf_mor_get_field_value($field);?>" />
            <?php glf_mor_display_error($field, $errors); ?>
        </td>
    </tr>
    <?php }

    // TODO: move this to restaurant-system class?  pretty similar with glf_mor_api_call
function glf_mor_remote_call($url, $mode) {
    $response = wp_remote_post( $url.($mode=='login' ? 'login3' : 'register'), array(
            'method' => 'POST',
            'headers' => array(),
            'body' => $_POST,
        )
    );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        die("Something went wrong: $error_message");
    }

    return json_decode($response['body']);
}

?>

<?php if ($this->is_authenticated()) {
    if (sizeof($this->restaurants) > 1) { ?>
        <h2>You have <u>several</u> restaurants that you manage!</h2>
        <div><strong>You may configure them in the <a href="<?= $this->get_glf_mor_token(); ?>" target="_blank">Advanced Admin Panel</a>.</strong></div>
    <?php } else {?>
        <div class="wrap">
            <h1><?php _e('Welcome to your restaurant dashboard');?></h1>
            <h2><?php _e("Let's get you started:");?></h2>

            <div class="glf-white-box setup glf-d-flex">
                <div>
                    <img class="glf-setup-img" src="<?= plugins_url('../images/restaurant_setup.png', __FILE__)?>">
                </div>
                <div>
                    <strong><?php _e("1. Set up your restaurant profile");?></strong><br>
                    <?php _e("Fill in your restaurant basics and install the restaurant app for taking orders and reservations."); ?>
                </div>
                <div class="glf-ml-auto">
                    <a href="<?= $this->iframe_src('setup');?>" target="_blank" class="button-primary  glf-btn-setup"> <?php _e('Get started');?></a>
                </div>
            </div>

            <div class="glf-white-box setup glf-d-flex">
                <div>
                    <img class="glf-setup-img" src="<?= plugins_url('../images/food_menu.png', __FILE__)?>">
                </div>
                <div>
                    <strong><?php _e("2. Insert the menu");?></strong><br>
                    <?php _e("Our editor makes any menu, however complex, easy to insert and use
."); ?>
                </div>
                <div class="glf-ml-auto">
                     <a href="<?= $this->iframe_src('menu');?>" target="_blank" class="button-primary glf-btn-setup"> <?php _e('Insert the menu');?></a>
                </div>
            </div>

            <div class="glf-white-box setup glf-d-flex">
                <div>
                    <img class="glf-setup-img" src="<?= plugins_url('../images/publish.png', __FILE__)?>">
                </div>
                <div>
                    <strong><?php _e("3. Publish on your website");?></strong><br>
                    <?php _e("Get the shortcodes to publish the widgets on your pages."); ?>
                </div>
                <div class="glf-ml-auto">
                    <a href="admin.php?page=glf-publishing"  class="button-primary glf-btn-setup"> <?php _e('Publish');?></a>
                </div>
            </div>


        </div>
    <?php }
    } else {
    ?>
<div class="wrap">
    <H1><?= glf_mor_field_to_label($mode);?></H1>
    <form class="glf-auth-form" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post"
          name="<?php echo $mode; ?>">
        <div class="welcome-panel">
            <div class="glf-auth-form-container">
                <div class="glf-center">
                    <?php glf_mor_display_error('form', $errors);?>
                </div>
                    <table class="form-table">
                        <tbody>
                        <?php foreach ($fields[$mode] as $field) {
                            glf_mor_display_form_field($field, $errors);
                        } ?>
                        <tr>
                            <td colspan="2">
                                <p class="submit">
                                    <?php if ($mode == 'sign_up') {
                                        _e('By signing up you agree to our', 'menu-ordering-reservations');?>
                                        <a href="https://www.gloriafood.com/admin/public/restaurant-terms" target="_blank"><?php _e('terms', 'menu-ordering-reservations');?></a>
                                         &  <a href="https://www.gloriafood.com/privacy" target="_blank"><?php _e('privacy policy', 'menu-ordering-reservations');?></a>
                                    <?php }?>
                                    <?php wp_nonce_field('glf-mor-auth') ?>
                                    <input type="submit" class="button-primary alignright" value="<?= glf_mor_field_to_label($mode);?>" name="submit"/>
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <input type="hidden" name="source" value="WORDPRESS">
                    <input type="hidden" name="installation_id" value="<?= $this->installation_id;?>">
                <?php
                if ($mode == 'sign_up') {?>
                    <input type="hidden" name="account_source" value="wp-plugin">
                    <input type="hidden" name="account_type" value="restaurant">
                    <input type="hidden" name="campaign" value="">
                    <input type="hidden" name="keyword" value="">
                    <input type="hidden" name="language_code" value="<?=get_user_locale();?>">
                    <input type="hidden" name="keyword" value="">
                    <input type="hidden" name="phone" value="">
                    <input type="hidden" name="signup_source" value="wordpress">
                    <input type="hidden" name="type" value="login">
                    <input type="hidden" name="website" value="">
                <?php }?>
            </div>
        </div>
        <div class="glf-center">
            <a href="<?php menu_page_url('glf-admin', true);?>&<?=$mode=='login' ? 'sign-up' : 'login';?>=true"><?php $mode =='login' ? _e( 'Create a restaurant account', 'menu-ordering-reservations') : _e('I already have a restaurant account', 'menu-ordering-reservations');?></a>
        </div>
    </form>
</div>
<?php }?>