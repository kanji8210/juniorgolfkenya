/**
 * Junior Golf Kenya Registration Form JavaScript
 * Handles multi-step form navigation, validation, and user interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Debug logging function
    function debugLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: '#3b82f6',
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b'
        };
        console.log(`%c[JGK Form ${timestamp}] ${message}`, `color: ${colors[type]}; font-weight: bold;`);
    }

    debugLog('Initializing Junior Golf Kenya Registration Form', 'info');

    // Get all form elements
    const steps = document.querySelectorAll('.jgk-form-step');
    const nextButtons = document.querySelectorAll('.jgk-btn-next');
    const prevButtons = document.querySelectorAll('.jgk-btn-prev');
    const progressSteps = document.querySelectorAll('.jgk-progress-step');

    debugLog(`Found ${steps.length} form steps`, 'info');
    debugLog(`Found ${nextButtons.length} next buttons`, 'info');
    debugLog(`Found ${prevButtons.length} previous buttons`, 'info');
    debugLog(`Found ${progressSteps.length} progress steps`, 'info');

    // Initialize first step
    if (steps.length > 0) {
        showStep(1);
        updateProgress(1);
        debugLog('Form initialized with step 1', 'success');
    } else {
        debugLog('No form steps found!', 'error');
    }

    // Next button click handlers
    nextButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const currentStep = getCurrentStep();
            debugLog(`Next button clicked from step ${currentStep}`, 'info');
            
            if (validateStep(currentStep)) {
                const nextStep = currentStep + 1;
                debugLog(`Step ${currentStep} validation passed - proceeding to step ${nextStep}`, 'success');
                showStep(nextStep);
                updateProgress(nextStep);
                debugLog(`Moved to step ${nextStep}`, 'success');
            } else {
                debugLog(`Step ${currentStep} validation failed - cannot proceed`, 'error');
            }
        });
    });

    // Previous button click handlers
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            debugLog('Previous button clicked for step: ' + this.dataset.prev, 'info');
            const prevStep = parseInt(this.dataset.prev);
            showStep(prevStep);
            updateProgress(prevStep);
            debugLog(`Moved back to step ${prevStep}`, 'info');
        });
    });

    function getCurrentStep() {
        const currentStepElement = document.querySelector('.jgk-form-step.active');
        if (currentStepElement) {
            return parseInt(currentStepElement.dataset.step);
        }
        debugLog('Could not find current step element', 'error');
        return 1;
    }

    function showStep(stepNumber) {
        debugLog(`Attempting to show step ${stepNumber}`, 'info');
        
        // Hide all steps
        steps.forEach(step => {
            step.classList.remove('active');
            debugLog(`Hiding step ${step.dataset.step}`, 'info');
        });
        
        // Show current step
        const currentStep = document.querySelector(`.jgk-form-step[data-step="${stepNumber}"]`);
        if (currentStep) {
            currentStep.classList.add('active');
            debugLog(`Showing step ${stepNumber}`, 'success');
        } else {
            debugLog(`Step ${stepNumber} element not found!`, 'error');
        }
    }

    function updateProgress(stepNumber) {
        debugLog(`Updating progress to step ${stepNumber}`, 'info');
        progressSteps.forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            if (stepNum <= stepNumber) {
                step.classList.add('active');
                debugLog(`Progress step ${stepNum} marked as active`, 'info');
            } else {
                step.classList.remove('active');
                debugLog(`Progress step ${stepNum} marked as inactive`, 'info');
            }
        });
    }

    function validateStep(stepNumber) {
        debugLog(`Starting validation for step ${stepNumber}`, 'info');
        const currentStep = document.querySelector(`.jgk-form-step[data-step="${stepNumber}"]`);
        const requiredFields = currentStep.querySelectorAll('[required]');
        let isValid = true;
        let validationErrors = [];

        debugLog(`Found ${requiredFields.length} required fields in step ${stepNumber}`, 'info');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                validationErrors.push(`Field '${field.name}' is required`);
                field.style.borderColor = '#ef4444';
                
                // Add error message
                if (!field.parentNode.querySelector('.field-error')) {
                    const errorMsg = document.createElement('small');
                    errorMsg.className = 'field-error';
                    errorMsg.style.color = '#ef4444';
                    errorMsg.textContent = 'This field is required';
                    field.parentNode.appendChild(errorMsg);
                    debugLog(`Added error message for field: ${field.name}`, 'error');
                }
            } else {
                field.style.borderColor = '#d1d5db';
                const errorMsg = field.parentNode.querySelector('.field-error');
                if (errorMsg) {
                    errorMsg.remove();
                    debugLog(`Removed error message for field: ${field.name}`, 'info');
                }
            }
        });

        // Special validation for step 1 (age)
        if (stepNumber === 1) {
            debugLog('Running special validation for step 1 (age)', 'info');
            const dobField = document.getElementById('date_of_birth');
            if (dobField && dobField.value) {
                const ageValid = validateAge(dobField.value);
                if (!ageValid) {
                    isValid = false;
                    validationErrors.push('Age validation failed');
                }
            }
        }

        // Special validation for step 3 (parent contact)
        if (stepNumber === 3) {
            debugLog('Running special validation for step 3 (parent contact)', 'info');
            const parentEmail = document.getElementById('parent_email');
            const parentPhone = document.getElementById('parent_phone');
            
            if ((!parentEmail.value.trim() && !parentPhone.value.trim())) {
                isValid = false;
                validationErrors.push('At least one parent contact method is required');
                const errorMsg = document.createElement('small');
                errorMsg.className = 'field-error';
                errorMsg.style.color = '#ef4444';
                errorMsg.textContent = 'At least one parent contact method is required';
                
                if (parentEmail.value.trim()) {
                    parentPhone.parentNode.appendChild(errorMsg.cloneNode(true));
                } else if (parentPhone.value.trim()) {
                    parentEmail.parentNode.appendChild(errorMsg.cloneNode(true));
                } else {
                    parentEmail.parentNode.appendChild(errorMsg.cloneNode(true));
                    parentPhone.parentNode.appendChild(errorMsg.cloneNode(true));
                }
                debugLog('Parent contact validation failed', 'error');
            } else {
                debugLog('Parent contact validation passed', 'success');
            }
        }

        if (!isValid) {
            debugLog(`Step ${stepNumber} validation failed with errors: ${validationErrors.join(', ')}`, 'error');
            // Scroll to first error
            const firstError = currentStep.querySelector('[required]:invalid') || currentStep.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                debugLog('Scrolled to first validation error', 'info');
            }
        } else {
            debugLog(`Step ${stepNumber} validation passed successfully`, 'success');
        }

        return isValid;
    }

    // Age validation
    function validateAge(dob) {
        debugLog(`Validating age for DOB: ${dob}`, 'info');
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        const messageDiv = document.getElementById('age-validation-message');
        if (!messageDiv) {
            debugLog('Age validation message div not found', 'error');
            return false;
        }
        
        if (age < 2) {
            messageDiv.style.background = '#fef2f2';
            messageDiv.style.color = '#dc2626';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.border = '1px solid #fecaca';
            messageDiv.innerHTML = '❌ The child must be at least 2 years old to register.';
            debugLog('Age validation failed: under 2 years', 'error');
            return false;
        } else if (age >= 18) {
            messageDiv.style.background = '#fef2f2';
            messageDiv.style.color = '#dc2626';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.border = '1px solid #fecaca';
            messageDiv.innerHTML = '❌ This program is reserved for juniors under 18 years old.';
            debugLog('Age validation failed: 18 years or older', 'error');
            return false;
        } else {
            messageDiv.style.background = '#f0fdf4';
            messageDiv.style.color = '#166534';
            messageDiv.style.padding = '12px';
            messageDiv.style.borderRadius = '8px';
            messageDiv.style.border = '1px solid #bbf7d0';
            messageDiv.innerHTML = `✅ Valid age: ${age} years old`;
            debugLog(`Age validation passed: ${age} years old`, 'success');
            return true;
        }
    }

    // Profile image handling
    const profileImageInput = document.getElementById('profile_image');
    const profileImageLabel = document.getElementById('profile_image_label');

    if (profileImageInput && profileImageLabel) {
        profileImageInput.addEventListener('change', function() {
            const fileName = this.files && this.files.length ? this.files[0].name : 'Choose an image';
            profileImageLabel.textContent = fileName;
            debugLog(this.files && this.files.length ? 'Profile image selected: ' + fileName : 'Profile image selection cleared', 'info');
        });
    }

    // Real-time age validation
    const dobField = document.getElementById('date_of_birth');
    if (dobField) {
        dobField.addEventListener('change', function() {
            debugLog('Date of birth changed: ' + this.value, 'info');
            validateAge(this.value);
        });
    }

    // Password match validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
            debugLog('Password validation failed: passwords do not match', 'error');
        } else {
            confirmPassword.setCustomValidity('');
            debugLog('Password validation passed', 'success');
        }
    }

    if (password && confirmPassword) {
        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    }

    // Password strength indicator
    if (password) {
        password.addEventListener('input', function() {
            const value = this.value;
            const strength = {
                0: 'Very Weak',
                1: 'Weak',
                2: 'Fair',
                3: 'Good',
                4: 'Strong'
            };
            
            let score = 0;
            
            if (value.length >= 8) score++;
            if (value.length >= 12) score++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^a-zA-Z\d]/.test(value)) score++;
            
            const strengthText = strength[Math.min(score, 4)];
            const colors = ['#dc2626', '#dc2626', '#f59e0b', '#10b981', '#10b981'];
            
            // Remove existing indicator
            let indicator = this.parentElement.querySelector('.password-strength');
            if (indicator) {
                indicator.remove();
            }
            
            // Add new indicator if there's a password
            if (value.length > 0) {
                indicator = document.createElement('small');
                indicator.className = 'password-strength';
                indicator.style.color = colors[Math.min(score, 4)];
                indicator.style.fontWeight = 'bold';
                indicator.textContent = 'Password Strength: ' + strengthText;
                this.parentElement.appendChild(indicator);
                debugLog(`Password strength: ${strengthText} (score: ${score})`, 'info');
            }
        });
    }

    // Trigger validation on page load if fields are pre-filled
    if (dobField && dobField.value) {
        debugLog('Triggering age validation for pre-filled DOB', 'info');
        validateAge(dobField.value);
    }

    // Log initial state
    debugLog('Form initialization complete', 'success');
    debugLog('Next buttons found: ' + nextButtons.length, 'info');
    debugLog('Previous buttons found: ' + prevButtons.length, 'info');
    debugLog('Form steps found: ' + steps.length, 'info');
});