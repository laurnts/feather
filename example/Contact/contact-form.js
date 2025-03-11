/* ---------------------------------------------
 Contact form
 --------------------------------------------- */
$(document).ready(function(){
    // Dump all form fields to console to debug
    console.log('Form structure:', {
        formExists: $('#contact_form').length,
        allInputs: $('#contact_form input').length,
        allTextareas: $('#contact_form textarea').length,
        buttonExists: $('#submit_btn').length
    });
    
    // List all input elements on the page for debugging
    var formInputs = [];
    $('#contact_form input, #contact_form textarea').each(function() {
        formInputs.push({
            type: this.type,
            id: this.id,
            name: this.name,
            value: $(this).val()
        });
    });
    console.log('All form fields:', formInputs);
    
    $("#submit_btn").click(function(){
        // Collect all inputs and textareas
        var formData = {};
        var allFieldsFilled = true;
        
        $('#contact_form input, #contact_form textarea').each(function() {
            var $this = $(this);
            var fieldName = $this.attr('name') || $this.attr('id');
            var fieldValue = $this.val();
            
            if (fieldName && fieldName !== 'submit_btn') {
                formData[fieldName] = fieldValue;
                
                // Check if field is empty
                if (!fieldValue) {
                    $this.css('border-color', '#e41919');
                    allFieldsFilled = false;
                }
            }
        });
        
        console.log('Collected form data:', formData);
        
        // Ensure we have the required fields
        if (!formData.userName && !formData.name) {
            console.error('Name field not found or empty');
            allFieldsFilled = false;
        }
        
        if (!formData.userEmail && !formData.email) {
            console.error('Email field not found or empty');
            allFieldsFilled = false;
        }
        
        if (!formData.userMessage && !formData.message) {
            console.error('Message field not found or empty');
            allFieldsFilled = false;
        }
        
        // Normalize field names for server
        var post_data = {
            'userName': formData.userName || formData.name || '',
            'userEmail': formData.userEmail || formData.email || '',
            'userMessage': formData.userMessage || formData.message || ''
        };
        
        console.log('Sending data:', post_data);
        
        //everything looks good! proceed...
        if (allFieldsFilled) {
            // Show loading indicator
            $("#result").hide().html('<div class="info">Sending message...</div>').slideDown();
            
            // Set a timeout to prevent infinite waiting
            var ajaxTimeout = 30000; // 30 seconds
            
            // Using $.ajax instead of $.post for more control
            $.ajax({
                url: 'send-mail.php',
                type: 'POST',
                data: post_data,
                dataType: 'json',
                timeout: ajaxTimeout,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    console.log('Response received:', response);
                    var output;
                    
                    //load json data from server and output message     
                    if (response.type == 'error') {
                        output = '<div class="error">' + response.text + '</div>';
                    } else {
                        output = '<div class="success">' + response.text + '</div>';
                        
                        //reset values in all input fields
                        $('#contact_form input').val('');
                        $('#contact_form textarea').val('');
                    }
                    
                    $("#result").hide().html(output).slideDown();
                    
                    // Re-enable submit button if it was disabled
                    $("#submit_btn").prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    var errorMessage = 'An error occurred. Please try again later.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'The server took too long to respond. Please try again later.';
                    }
                    
                    $("#result").hide().html('<div class="error">' + errorMessage + '</div>').slideDown();
                    
                    // Re-enable submit button if it was disabled
                    $("#submit_btn").prop('disabled', false);
                }
            });
            
            // Disable submit button to prevent multiple submissions
            $("#submit_btn").prop('disabled', true);
        } else {
            $("#result").hide().html('<div class="error">Please fill in all required fields</div>').slideDown();
        }
        
        return false;
    });
    
    //reset previously set border colors and hide all message on .keyup()
    $("#contact_form input, #contact_form textarea").keyup(function(){
        $(this).css('border-color', '');
        $("#result").slideUp();
    });
});
