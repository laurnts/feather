<?php
use Laurnts\Feather\Mail\Smtp;
use Laurnts\Feather\Mail\SmtpConfig;

// Initialize SMTP components with Router
Smtp::setRouter($router);
SmtpConfig::setRouter($router);

// Debug the URL
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("[Contact] SMTP URL: " . $router->getUrl('vendor/laurnts/feather/src/Mail/Smtp.php'));
}
?>
<!-- Contact Section -->
<section class="page-section" id="contact">
    <div class="container position-relative">
    
        <div class="row">
            
            <div class="col-lg-6">
                
                <div class="row mb-50">
                    <div class="col-lg-10">
                        <h2 class="section-caption mb-xs-10">Contact Us</h2>
                        <h3 class="section-title mb-0"><span class="wow charsAnimIn" data-splitting="chars">Let's connect.</span></h3>
                    </div>
                </div>
                
            </div>
            
            <div class="col-lg-6">
                
                <div class="row mb-60 mb-sm-50">
                    
                    <!-- Contact Item -->
                    <div class="col-sm-6 mb-xs-30 d-flex align-items-stretch">
                        <div class="alt-features-item border-left mt-0 wow fadeScaleIn" data-wow-delay=".3s">
                            <div class="alt-features-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd">
                                    <path d="M24 21h-24v-18h24v18zm-23-16.477v15.477h22v-15.477l-10.999 10-11.001-10zm21.089-.523h-20.176l10.088 9.171 10.088-9.171z"/>
                                </svg>
                                <div class="alt-features-icon-s">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm3.445 17.827c-3.684 1.684-9.401-9.43-5.8-11.308l1.053-.519 1.746 3.409-1.042.513c-1.095.587 1.185 5.04 2.305 4.497l1.032-.505 1.76 3.397-1.054.516z"/>
                                    </svg>
                                </div>
                            </div>
                            <h4 class="alt-features-title">Say hello</h4>
                            <div class="alt-features-descr clearlinks">
                                <div><a href="mailto:info@aspina.nl">info@aspina.nl</a></div>
                            </div>
                        </div>
                    </div>
                    <!-- End Contact Item -->
                    
                    <!-- Contact Item -->
                    <div class="col-sm-6 d-flex align-items-stretch">
                        <div class="alt-features-item border-left mt-0 wow fadeScaleIn" data-wow-delay=".5s">
                            <div class="alt-features-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd">
                                    <path d="M12 10c-1.104 0-2-.896-2-2s.896-2 2-2 2 .896 2 2-.896 2-2 2m0-5c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3m-7 2.602c0-3.517 3.271-6.602 7-6.602s7 3.085 7 6.602c0 3.455-2.563 7.543-7 14.527-4.489-7.073-7-11.072-7-14.527m7-7.602c-4.198 0-8 3.403-8 7.602 0 4.198 3.469 9.21 8 16.398 4.531-7.188 8-12.2 8-16.398 0-4.199-3.801-7.602-8-7.602"/>
                                </svg>
                            </div>
                            <h4 class="alt-features-title">Location</h4>
                            <div class="alt-features-descr">
                                Indonesia House Amsterdam, IHA<br/>
                                Brachthuijszestraat 4<br/>
                                1075 EN, Amsterdam<br/>
                                The Netherlands
                            </div>
                        </div>
                    </div>
                    <!-- End Contact Item -->
                    
                </div>
                
            </div>
            
        </div>
        
        <div class="row wow fadeInUp" data-wow-delay="0.5s">
            
            <div class="col-md-6 mb-sm-50">
                
                <!-- Contact Form -->
                <form class="form contact-form pe-lg-5" id="contact_form">
                    
                    <div class="row">
                        <div class="col-lg-6">
                            
                            <!-- Name -->
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" id="name" class="input-lg round form-control" placeholder="Enter your name" pattern=".{3,100}" required aria-required="true">
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            
                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="input-lg round form-control" placeholder="Enter your email" pattern=".{5,100}" required aria-required="true">
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Message -->
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea name="message" id="message" class="input-lg round form-control" style="height: 130px;" placeholder="Enter your message"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-5">
                            
                            <!-- Send Button -->
                            <div class="pt-20">
                                <button class="submit_btn btn btn-mod btn-large btn-round btn-hover-anim" id="submit_btn" aria-controls="result">
                                    <span>Send Message</span>
                                </button>
                            </div>   
                                                             
                        </div>
                        
                        <div class="col-lg-7">
                            
                            <!-- Inform Tip -->
                            <div class="form-tip pt-20 pt-sm-0 mt-sm-20">
                                <i class="icon-info size-16"></i>
                                By sending the form you agree to our <a href="<?php echo $router->getUrl('privacy-policy'); ?>">Privacy Policy</a>.
                            </div>
                            
                        </div>
                    </div>
                   
                   <div id="result" role="region" aria-live="polite" aria-atomic="true"></div>
                   
                </form>
                <!-- End Contact Form -->
                
            </div>
            
            <div class="col-md-6 d-flex align-items-stretch">
                
                <!-- Google Map -->
                <div class="map-boxed">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2436.4599431242287!2d4.869673776897973!3d52.35108667968088!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c609e8c7a6a6c9%3A0x4c2f0b0c2e4c0c0e!2sIndonesia%20House%20Amsterdam!5e0!3m2!1sen!2snl!4v1710321641234!5m2!1sen!2snl" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <!-- End Google Map -->
                
            </div>
            
        </div>                   
    
    </div>
</section>
<!-- End Contact Section -->

<!-- Contact Form JavaScript -->
<script>
document.getElementById('contact_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var submitButton = document.getElementById('submit_btn');
    var result = document.getElementById('result');
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = 'Sending...';
    
    // Get form values
    var name = document.getElementById('name').value;
    var email = document.getElementById('email').value;
    var message = document.getElementById('message').value;
    
    // Debug log
    console.log('Form data to be sent:', {
        userName: name,
        userEmail: email,
        userMessage: message ? message.substring(0, 30) + '...' : ''
    });
    
    // Get the full absolute URL
    var formEndpoint = '<?php echo $router->getUrl('send-mail.php'); ?>';
    console.log('Submit URL:', formEndpoint);
    
    // Make sure URL starts with slash if it's a relative URL
    if (formEndpoint.indexOf('http') !== 0 && formEndpoint.charAt(0) !== '/') {
        formEndpoint = '/' + formEndpoint;
        console.log('Fixed URL:', formEndpoint);
    }
    
    // Create form data
    var formData = new FormData();
    formData.append('userName', name);
    formData.append('userEmail', email);
    formData.append('userMessage', message);
    
    // Log headers
    console.log('Request headers:', {
        'X-Requested-With': 'XMLHttpRequest'
    });
    
    // Use XMLHttpRequest for better compatibility
    var xhr = new XMLHttpRequest();
    xhr.open('POST', formEndpoint, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        console.log('XHR status:', xhr.status);
        console.log('XHR response text:', xhr.responseText);
        
        try {
            var response = JSON.parse(xhr.responseText);
            console.log('Parsed response:', response);
            
            if (response.type == 'error') {
                result.innerHTML = '<div class="alert alert-danger round" role="alert">' + response.text + '</div>';
            } else {
                result.innerHTML = '<div class="alert alert-success round" role="alert">' + response.text + '</div>';
                
                // Reset form if successful
                document.getElementById('name').value = '';
                document.getElementById('email').value = '';
                document.getElementById('message').value = '';
            }
        } catch (e) {
            console.error('Error parsing response:', e);
            result.innerHTML = '<div class="alert alert-danger round" role="alert">An error occurred. Please try again later.</div>';
        }
        
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = '<span>Send Message</span>';
        
        // Show result
        result.style.display = 'block';
    };
    
    xhr.onerror = function() {
        console.error('XHR error');
        
        // Try fallback with absolute URL
        console.log('Trying fallback URL');
        var fallbackUrl = window.location.origin + '/send-mail.php';
        console.log('Fallback URL:', fallbackUrl);
        
        var fallbackXhr = new XMLHttpRequest();
        fallbackXhr.open('POST', fallbackUrl, true);
        fallbackXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        fallbackXhr.onload = function() {
            console.log('Fallback XHR status:', fallbackXhr.status);
            console.log('Fallback XHR response text:', fallbackXhr.responseText);
            
            try {
                var response = JSON.parse(fallbackXhr.responseText);
                console.log('Fallback parsed response:', response);
                
                if (response.type == 'error') {
                    result.innerHTML = '<div class="alert alert-danger round" role="alert">' + response.text + '</div>';
                } else {
                    result.innerHTML = '<div class="alert alert-success round" role="alert">' + response.text + '</div>';
                    
                    // Reset form if successful
                    document.getElementById('name').value = '';
                    document.getElementById('email').value = '';
                    document.getElementById('message').value = '';
                }
            } catch (e) {
                console.error('Error parsing fallback response:', e);
                result.innerHTML = '<div class="alert alert-danger round" role="alert">An error occurred. Please try again later.</div>';
            }
            
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.innerHTML = '<span>Send Message</span>';
            
            // Show result
            result.style.display = 'block';
        };
        
        fallbackXhr.onerror = function() {
            console.error('Fallback XHR error');
            result.innerHTML = '<div class="alert alert-danger round" role="alert">Failed to send message. Please try again later.</div>';
            
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.innerHTML = '<span>Send Message</span>';
            
            // Show result
            result.style.display = 'block';
        };
        
        fallbackXhr.send(formData);
    };
    
    xhr.send(formData);
});
</script> 