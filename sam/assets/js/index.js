const OPENROUTER_API_KEY = "your-openrouter-api-key-here"; // Replace with your OpenRouter API key
        const OPENROUTER_API_URL = "https://openrouter.ai/api/v1/chat/completions";
        
        // Indian Languages Mapping
        const INDIAN_LANGUAGES = {
            'hi': 'Hindi',
            'en': 'English',
            'bn': 'Bengali',
            'ta': 'Tamil',
            'te': 'Telugu',
            'mr': 'Marathi',
            'gu': 'Gujarati',
            'kn': 'Kannada',
            'ml': 'Malayalam',
            'or': 'Odia',
            'pa': 'Punjabi',
            'as': 'Assamese',
            'ur': 'Urdu',
            'ne': 'Nepali',
            'sd': 'Sindhi',
            'kok': 'Konkani',
            'mai': 'Maithili',
            'sat': 'Santali',
            'ks': 'Kashmiri',
            'doi': 'Dogri',
            'mni': 'Manipuri'
        };
        
        // Function to translate text using OpenRouter API
        async function translateText(text, targetLang) {
            try {
                const response = await fetch(OPENROUTER_API_URL, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${OPENROUTER_API_KEY}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        model: "google/gemini-pro",
                        messages: [
                            {
                                role: "system",
                                content: `You are a professional translator. Translate the following text to ${INDIAN_LANGUAGES[targetLang] || 'English'}. Return only the translation, no explanations.`
                            },
                            {
                                role: "user",
                                content: text
                            }
                        ],
                        temperature: 0.3,
                        max_tokens: 1000
                    })
                });

                const data = await response.json();
                
                if (data.choices && data.choices[0] && data.choices[0].message) {
                    return data.choices[0].message.content.trim();
                } else {
                    throw new Error('Translation failed');
                }
            } catch (error) {
                console.error('Translation error:', error);
                return text; // Return original text if translation fails
            }
        }

        // Function to translate all text on the page
        async function translatePage(targetLang) {
            // Show loading indicator
            const loadingIndicator = document.createElement('div');
            loadingIndicator.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                         background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.2);
                         z-index: 9999; display: flex; align-items: center; gap: 10px;">
                    <div class="spinner-border text-primary"></div>
                    <span>Translating to ${INDIAN_LANGUAGES[targetLang] || 'Selected Language'}...</span>
                </div>
            `;
            document.body.appendChild(loadingIndicator);

            try {
                // Get all translatable elements
                const elementsToTranslate = document.querySelectorAll('[id]');
                const translationPromises = [];

                for (const element of elementsToTranslate) {
                    const text = element.textContent.trim();
                    if (text && !element.hasAttribute('data-translated')) {
                        translationPromises.push(
                            translateText(text, targetLang).then(translatedText => {
                                element.textContent = translatedText;
                                element.setAttribute('data-translated', 'true');
                            })
                        );
                    }
                }

                // Translate placeholder texts
                const placeholders = document.querySelectorAll('[placeholder]');
                for (const element of placeholders) {
                    const placeholderText = element.getAttribute('placeholder');
                    if (placeholderText) {
                        translationPromises.push(
                            translateText(placeholderText, targetLang).then(translatedText => {
                                element.setAttribute('placeholder', translatedText);
                            })
                        );
                    }
                }

                await Promise.all(translationPromises);
                
                // Update URL and reload to get server-side translations for dynamic content
                window.location.href = `index.php?lang=${targetLang}&page=${getCurrentPage()}`;
            } catch (error) {
                console.error('Page translation error:', error);
                // Fallback to server-side translation
                window.location.href = `index.php?lang=${targetLang}&page=${getCurrentPage()}`;
            } finally {
                // Remove loading indicator
                if (document.body.contains(loadingIndicator)) {
                    document.body.removeChild(loadingIndicator);
                }
            }
        }

        // Get current page number
        function getCurrentPage() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 1;
        }

        // Language change function
        function changeLanguage(lang) {
            // Close mobile language switcher if open
            closeLanguageSwitcher();
            
            // Check if we're already in the target language
            const currentLang = '<?php echo $currentLang; ?>';
            if (lang === currentLang) {
                return;
            }

            // Use OpenRouter API for translation
            translatePage(lang);
        }

        // Toggle mobile language switcher
        function toggleLanguageSwitcher() {
            const switcher = document.getElementById('mobileLanguageSwitcher');
            switcher.classList.toggle('open');
        }

        // Close mobile language switcher
        function closeLanguageSwitcher() {
            const switcher = document.getElementById('mobileLanguageSwitcher');
            switcher.classList.remove('open');
        }

        // Close language switcher when clicking outside
        document.addEventListener('click', function(event) {
            const switcher = document.getElementById('mobileLanguageSwitcher');
            const icon = document.querySelector('.mobile-language-icon');
            
            if (switcher.classList.contains('open') && 
                !switcher.contains(event.target) && 
                !icon.contains(event.target)) {
                closeLanguageSwitcher();
            }
        });

        // Handle gender selection
        document.querySelectorAll('.gender-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.gender-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('joinGender').value = this.dataset.gender;
            });
        });

        // Handle desktop language switcher
        document.getElementById('languageSelect')?.addEventListener('change', function() {
            changeLanguage(this.value);
        });

        // Function to validate phone number (10 digits)
        function validatePhoneNumber(phoneNumber) {
            const phoneRegex = /^[0-9]{10}$/;
            return phoneRegex.test(phoneNumber);
        }

        // Function to show phone validation message
        function showPhoneValidation(inputId, validationId, isValid) {
            const validationElement = document.getElementById(validationId);
            if (!validationElement) return;
            
            if (isValid) {
                validationElement.textContent = 'Valid phone number';
                validationElement.className = 'phone-validation valid';
                validationElement.style.display = 'block';
            } else {
                validationElement.textContent = 'Enter a valid 10-digit mobile number';
                validationElement.className = 'phone-validation invalid';
                validationElement.style.display = 'block';
            }
            
            // Hide validation message after 3 seconds
            setTimeout(() => {
                validationElement.style.display = 'none';
            }, 3000);
        }

        // Function to count words
        function countWords(text) {
            // Remove leading/trailing whitespace
            text = text.trim();
            
            // If empty, return 0
            if (text === '') {
                return 0;
            }
            
            // Split by whitespace and filter out empty strings
            const words = text.split(/\s+/).filter(word => word.length > 0);
            return words.length;
        }

        // Function to update word count display
        function updateWordCount() {
            const textarea = document.getElementById('userOpinion');
            const wordCountElement = document.getElementById('wordCount');
            const wordLimitMessage = document.getElementById('wordLimitMessage');
            const submitBtn = document.getElementById('submitOpinionBtn');
            
            if (!textarea || !wordCountElement) return;
            
            const text = textarea.value;
            const wordCount = countWords(text);
            
            // Update word count display
            wordCountElement.textContent = `Words: ${wordCount}/20`;
            
            // Update styling based on word count
            if (wordCount > 20) {
                wordCountElement.classList.add('limit-reached');
                wordCountElement.classList.remove('warning', 'valid');
                wordLimitMessage.textContent = 'Maximum 20 words allowed!';
                wordLimitMessage.style.color = '#c0392b';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            } else if (wordCount > 15) {
                wordCountElement.classList.add('warning');
                wordCountElement.classList.remove('limit-reached', 'valid');
                wordLimitMessage.textContent = `${20 - wordCount} words remaining`;
                wordLimitMessage.style.color = '#e74c3c';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                wordCountElement.classList.add('valid');
                wordCountElement.classList.remove('warning', 'limit-reached');
                wordLimitMessage.textContent = wordCount === 0 ? 'Enter your opinion' : `${20 - wordCount} words remaining`;
                wordLimitMessage.style.color = wordCount === 0 ? '#666' : '#27ae60';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        }

        // Function to validate opinion form before submission
        function validateOpinionForm(event) {
            const textarea = document.getElementById('userOpinion');
            const wordCount = countWords(textarea.value);
            
            if (wordCount > 20) {
                event.preventDefault();
                alert('Please limit your opinion to maximum 20 words. You have entered ' + wordCount + ' words.');
                textarea.focus();
                return false;
            }
            
            if (wordCount === 0) {
                event.preventDefault();
                alert('Please enter your opinion.');
                textarea.focus();
                return false;
            }
            
            return true;
        }

        // Function to validate join form before submission
        function validateJoinForm(event) {
            const phoneInput = document.getElementById('joinPhone');
            const phoneNumber = phoneInput.value.trim();
            
            if (!validatePhoneNumber(phoneNumber)) {
                event.preventDefault();
                showPhoneValidation('joinPhone', 'joinPhoneValidation', false);
                phoneInput.focus();
                return false;
            }
            
            return true;
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize word count for opinion textarea
            const opinionTextarea = document.getElementById('userOpinion');
            if (opinionTextarea) {
                // Add event listeners for input
                opinionTextarea.addEventListener('input', updateWordCount);
                opinionTextarea.addEventListener('keyup', updateWordCount);
                opinionTextarea.addEventListener('paste', function(e) {
                    // Delay to allow paste to complete
                    setTimeout(updateWordCount, 10);
                });
                
                // Initialize word count display
                updateWordCount();
                
                // Add form validation
                const opinionForm = document.getElementById('opinionForm');
                if (opinionForm) {
                    opinionForm.addEventListener('submit', validateOpinionForm);
                }
            }
            
            // Add phone number validation for join form
            const joinPhoneInput = document.getElementById('joinPhone');
            if (joinPhoneInput) {
                joinPhoneInput.addEventListener('input', function() {
                    const phoneNumber = this.value.trim();
                    if (phoneNumber.length === 10) {
                        showPhoneValidation('joinPhone', 'joinPhoneValidation', validatePhoneNumber(phoneNumber));
                    }
                });
                
                joinPhoneInput.addEventListener('blur', function() {
                    const phoneNumber = this.value.trim();
                    if (phoneNumber) {
                        showPhoneValidation('joinPhone', 'joinPhoneValidation', validatePhoneNumber(phoneNumber));
                    }
                });
                
                // Add form validation
                const joinForm = document.getElementById('joinForm');
                if (joinForm) {
                    joinForm.addEventListener('submit', validateJoinForm);
                }
            }
            
            // Add phone number validation for opinion form (optional phone)
            const opinionPhoneInput = document.getElementById('userPhone');
            if (opinionPhoneInput) {
                opinionPhoneInput.addEventListener('input', function() {
                    const phoneNumber = this.value.trim();
                    if (phoneNumber.length === 10) {
                        showPhoneValidation('userPhone', 'opinionPhoneValidation', validatePhoneNumber(phoneNumber));
                    }
                });
                
                opinionPhoneInput.addEventListener('blur', function() {
                    const phoneNumber = this.value.trim();
                    if (phoneNumber) {
                        showPhoneValidation('userPhone', 'opinionPhoneValidation', validatePhoneNumber(phoneNumber));
                    }
                });
            }
            
            // Only allow numbers in phone fields
            document.querySelectorAll('input[type="tel"]').forEach(input => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });
            
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function() {
                    document.body.classList.add('modal-open');
                    closeLanguageSwitcher(); // Close language switcher when modal opens
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.classList.remove('modal-open');
                });
            });
            
            // Auto remove alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert-message').forEach(alert => {
                    alert.classList.remove('show');
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                });
            }, 5000);
        });

        // Handle form submissions with translation
        document.addEventListener('submit', function(e) {
            if (e.target.matches('#joinForm, #opinionForm, #loginForm')) {
                // Close language switcher if open
                closeLanguageSwitcher();
            }
        });