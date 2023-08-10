<form id="enquiry_form">
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
                data: form.serialize()
            })
            // alert("test ");
        })
    })

</script>