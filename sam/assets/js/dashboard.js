 const TRANSLATION_CONFIG = {
            apiKey: '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', // Your OpenRouter API Key
            endpoint: 'https://openrouter.ai/api/v1/chat/completions',
            model: 'openai/gpt-3.5-turbo',
            targetLanguage: '<?php echo $current_language; ?>'
        };
        
        // Language names mapping
        const LANGUAGE_NAMES = {
            'en': 'English',
            'hi': 'हिन्दी',
            'es': 'Español',
            'fr': 'Français',
            'de': 'Deutsch',
            'ja': '日本語'
        };
        
        // Store original texts
        let originalTexts = new Map();
        let isTranslating = false;
        
        // Initialize on DOM load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all translatable texts
            storeOriginalTexts();
            
            // If language is not English, translate the page
            if (TRANSLATION_CONFIG.targetLanguage !== 'en') {
                translatePage(TRANSLATION_CONFIG.targetLanguage);
            }
            
            // Setup existing functionality
            setupExistingFunctionality();
        });
        
        function storeOriginalTexts() {
            document.querySelectorAll('.translatable').forEach(element => {
                const key = element.getAttribute('data-key') || element.textContent;
                originalTexts.set(element, element.innerHTML);
            });
        }
        
        async function switchLanguage(newLanguage) {
            if (isTranslating) return;
            
            isTranslating = true;
            showLoadingOverlay(LANGUAGE_NAMES[newLanguage] || newLanguage);
            
            try {
                // Update session via AJAX
                await updateSessionLanguage(newLanguage);
                
                // Update target language
                TRANSLATION_CONFIG.targetLanguage = newLanguage;
                
                // Update body attribute
                document.body.setAttribute('data-language', newLanguage);
                
                // Translate the page
                if (newLanguage === 'en') {
                    // Restore original English texts
                    restoreOriginalTexts();
                    showSuccessMessage('Language switched to English');
                } else {
                    // Translate to new language
                    await translatePage(newLanguage);
                    showSuccessMessage(`Language switched to ${LANGUAGE_NAMES[newLanguage] || newLanguage}`);
                }
            } catch (error) {
                console.error('Language switch error:', error);
                showErrorMessage('Failed to switch language. Please try again.');
            } finally {
                hideLoadingOverlay();
                isTranslating = false;
            }
        }
        
        async function updateSessionLanguage(language) {
            return fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=switch_language&language=${language}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Failed to update session');
                }
                return data;
            });
        }
        
        async function translatePage(targetLanguage) {
            // Get all translatable elements
            const translatableElements = Array.from(document.querySelectorAll('.translatable'));
            
            if (translatableElements.length === 0) return;
            
            // Group texts by approximate length to avoid API limits
            const textGroups = groupTextsForTranslation(translatableElements);
            
            // Translate each group
            for (const group of textGroups) {
                await translateTextGroup(group, targetLanguage);
            }
        }
        
        function groupTextsForTranslation(elements) {
            const groups = [];
            let currentGroup = [];
            let currentLength = 0;
            const MAX_GROUP_LENGTH = 2000; // Characters per API call
            
            elements.forEach(element => {
                const text = originalTexts.get(element) || element.textContent;
                const textLength = text.length;
                
                if (currentLength + textLength > MAX_GROUP_LENGTH) {
                    groups.push(currentGroup);
                    currentGroup = [element];
                    currentLength = textLength;
                } else {
                    currentGroup.push(element);
                    currentLength += textLength;
                }
            });
            
            if (currentGroup.length > 0) {
                groups.push(currentGroup);
            }
            
            return groups;
        }
        
        async function translateTextGroup(elements, targetLanguage) {
            // Prepare texts for translation
            const texts = elements.map(element => {
                return originalTexts.get(element) || element.textContent;
            });
            
            try {
                // Call OpenRouter API
                const translatedTexts = await callOpenRouterAPI(texts, 'en', targetLanguage);
                
                // Apply translations to elements
                elements.forEach((element, index) => {
                    if (translatedTexts[index]) {
                        // Preserve HTML structure if original had HTML
                        const original = originalTexts.get(element);
                        if (original && original.includes('<')) {
                            // Simple HTML preservation - replace text nodes
                            element.innerHTML = translatedTexts[index];
                        } else {
                            element.textContent = translatedTexts[index];
                        }
                    }
                });
            } catch (error) {
                console.error('Translation error for group:', error);
                throw error;
            }
        }
        
        async function callOpenRouterAPI(texts, sourceLang, targetLang) {
            // Prepare the prompt
            const prompt = `Translate the following English text(s) to ${LANGUAGE_NAMES[targetLang] || targetLang}. 
            IMPORTANT: Return ONLY the translations in the EXACT SAME ORDER, separated by "|||". 
            Do not add any explanations, notes, or additional text.
            
            Texts to translate:
            ${texts.join(' ||| ')}`;
            
            try {
                const response = await fetch(TRANSLATION_CONFIG.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${TRANSLATION_CONFIG.apiKey}`,
                        'HTTP-Referer': window.location.origin,
                        'X-Title': 'Sarvatantra Dashboard'
                    },
                    body: JSON.stringify({
                        model: TRANSLATION_CONFIG.model,
                        messages: [
                            {
                                role: "system",
                                content: "You are a professional translator. Translate exactly what is given, maintaining the same format and order. Return only the translations separated by |||."
                            },
                            {
                                role: "user",
                                content: prompt
                            }
                        ],
                        max_tokens: 4000,
                        temperature: 0.1
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API error (${response.status}): ${errorText}`);
                }
                
                const data = await response.json();
                
                if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                    throw new Error('Invalid API response format');
                }
                
                const translatedText = data.choices[0].message.content.trim();
                
                // Split the response
                const translations = translatedText.split('|||').map(text => text.trim());
                
                if (translations.length !== texts.length) {
                    console.warn(`Translation count mismatch: expected ${texts.length}, got ${translations.length}`);
                }
                
                return translations;
                
            } catch (error) {
                console.error('OpenRouter API call failed:', error);
                throw error;
            }
        }
        
        function restoreOriginalTexts() {
            originalTexts.forEach((text, element) => {
                element.innerHTML = text;
            });
        }
        
        function showLoadingOverlay(languageName) {
            // Remove existing overlay
            hideLoadingOverlay();
            
            const overlay = document.createElement('div');
            overlay.id = 'translationLoading';
            overlay.className = 'translation-loading';
            overlay.innerHTML = `
                <div class="translation-loading-content">
                    <div class="spinner-border text-light mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Switching to ${languageName}...</div>
                    <div class="mt-2" style="font-size: 0.9rem; opacity: 0.8;">Please wait while we translate the content</div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
        
        function hideLoadingOverlay() {
            const overlay = document.getElementById('translationLoading');
            if (overlay) {
                overlay.remove();
            }
        }
        
        function showSuccessMessage(message) {
            // Remove existing alerts
            document.querySelectorAll('.alert-message').forEach(alert => {
                alert.remove();
            });
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-message show';
            alertDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-2"></i>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.getElementById('alertContainer').appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 3000);
        }
        
        function showErrorMessage(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-message show';
            alertDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-2"></i>${message}</span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.getElementById('alertContainer').appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 5000);
        }
        
        function setupExistingFunctionality() {
            // Mobile menu toggle
            document.getElementById('mobileMenuToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const menuToggle = document.getElementById('mobileMenuToggle');
                
                if (window.innerWidth <= 992 && 
                    !sidebar.contains(event.target) && 
                    !menuToggle.contains(event.target) && 
                    sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
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
        }
        
        // Fixed opinion viewing function to fetch actual data from database
        async function viewOpinion(opinionId) {
            const modalBody = document.getElementById('opinionDetailBody');
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>Loading opinion details...</div>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('opinionDetailModal'));
            modal.show();
            
            // Store modal elements for translation
            storeOriginalTexts();
            
            try {
                // Fetch opinion data from server using POST request
                const response = await fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=fetch_opinion&opinion_id=${opinionId}`
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch opinion data');
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to load opinion');
                }
                
                const opinion = result.data;
                
                // Update modal with actual data - showing all fields in a nice layout
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-user me-2"></i><span class="translatable" data-key="name">Name</span></label>
                                <div class="value">${opinion.name}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-envelope me-2"></i><span class="translatable" data-key="email">Email</span></label>
                                <div class="value">${opinion.email}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-phone me-2"></i><span class="translatable" data-key="phone">Phone</span></label>
                                <div class="value">${opinion.phone}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-tag me-2"></i><span class="translatable" data-key="category">Category</span></label>
                                <div class="value">${opinion.category}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-calendar me-2"></i><span class="translatable" data-key="date_submitted">Date Submitted</span></label>
                                <div class="value">${opinion.submission_date}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-language me-2"></i><span class="translatable" data-key="language">Language</span></label>
                                <div class="value">${opinion.language}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-info-circle me-2"></i><span class="translatable" data-key="status">Status</span></label>
                                <div class="value">${opinion.status}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="opinion-detail">
                                <label><i class="fas fa-id-card me-2"></i><span class="translatable" data-key="opinion_id">Opinion ID</span></label>
                                <div class="value">${opinion.id}</div>
                            </div>
                        </div>
                    </div>
                    <div class="opinion-detail">
                        <label><i class="far fa-comment-dots me-2"></i><span class="translatable" data-key="opinion">Opinion</span></label>
                        <div class="value" style="min-height: 100px; white-space: pre-wrap; padding: 15px;">${opinion.opinion}</div>
                    </div>
                `;
                
                // Store modal elements for translation
                storeOriginalTexts();
                
            } catch (error) {
                console.error('Error loading opinion:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> Failed to load opinion details. Please try again.
                    </div>
                `;
            }
        }