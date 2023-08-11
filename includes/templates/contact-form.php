<div id="form_success" style="background:green; color:#fff;"></div>
<div id="form_error" style="background:red; color:#fff;"></div>

<form id="enquiry_form">
    <?php wp_nonce_field( 'wp_rest') ?>
    <label>Name : <label>
    <input type="text" name="name"> <br /><br />
    <label>Email : <label>
    <input type="text" name="email"> <br /><br />
    <label>Phone : <label>
    <input type="text" name="phone"> <br /><br />
    <label>Your Message : <label><br />
    <textarea name="message"></textarea> <br /><br />
    <button type="submit">submit form</button>
</form>

<script>

    jQuery(document).ready(function($){
        $("#enquiry_form").submit( function(event){
            event.preventDefault();
            var form = $(this);
            alert(form.serialize());
            $.ajax({
                type:"POST",
                url:"<?php echo get_rest_url( null, 'v1/contact-form/submit');?>",
                data: form.serialize(),
                success:function(){
                    form.hide()
                    $("#form_success").html("Your Message was sent").fadeIn();
                },
                error:function(){
                    form.hide()
                    $("#form_error").html("Their was an error submiting your form").fadeIn();
                },
            })
        })
    })

</script>