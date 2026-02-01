/**
 * ChatFlow - Conversational Form Builder
 * @author TotalWebCreations
 * @version 1.0.0
 */

class ChatFlow {
  constructor(options = {}) {
    // Required options
    this.modalId = options.modalId;
    this.formHandle = options.formHandle;
    this.triggerId = options.triggerId;
    this.questions = options.questions || [];
    this.successMessage = options.successMessage || 'Thank you for your submission!';

    // Avatar settings
    this.avatarStyle = options.avatarStyle || '';
    this.showInitials = options.showInitials || false;
    this.initials = options.initials || 'CF';
    this.initialsColor = options.initialsColor || '#ffffff';

    // Translations
    this.translations = options.translations || {
      stepProgress: 'Step {current} of {total}',
      typeAnswer: 'Type your answer...',
      skipQuestion: 'Skip this question',
      errorGeneric: 'Something went wrong. Please try again later.',
      errorNetwork: 'An error occurred. Please try again later.'
    };

    // Callbacks
    this.onComplete = options.onComplete || null;
    this.onError = options.onError || null;

    // State
    this.currentStep = 0;
    this.userData = {};
    this.isProcessing = false;
    this.typingIndicatorElement = null;

    // DOM elements
    this.modal = document.getElementById(this.modalId);
    if (!this.modal) {
      console.error(`ChatFlow: Modal with ID "${this.modalId}" not found`);
      return;
    }

    this.modalContent = this.modal.querySelector('.chatflow-content');
    this.modalBackdrop = this.modal.querySelector('.chatflow-backdrop');
    this.closeButton = this.modal.querySelector('.chatflow-close');
    this.chatMessages = this.modal.querySelector('.chatflow-messages');
    this.chatInputArea = this.modal.querySelector('.chatflow-input-area');
    this.textInputContainer = this.modal.querySelector('.chatflow-text-input');
    this.quickRepliesContainer = this.modal.querySelector('.chatflow-quick-replies');
    this.chatTextForm = this.modal.querySelector('.chatflow-text-form');
    this.chatTextInput = this.modal.querySelector('.chatflow-input');
    this.quickReplies = this.modal.querySelector('.chatflow-buttons');
    this.chatProgress = this.modal.querySelector('.chatflow-progress');
    this.progressBars = this.modal.querySelectorAll('.chatflow-progress-bar');

    // Trigger button
    const trigger = document.getElementById(this.triggerId);
    if (trigger) {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        this.open();
      });
    }

    this.init();
  }

  init() {
    // Close button
    if (this.closeButton) {
      this.closeButton.addEventListener('click', () => this.close());
    }

    // Backdrop click
    if (this.modalBackdrop) {
      this.modalBackdrop.addEventListener('click', () => this.close());
    }

    // Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
        this.close();
      }
    });

    // Text form submission
    if (this.chatTextForm) {
      this.chatTextForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleTextInput();
      });
    }
  }

  /**
   * Initialize spam protection fields
   * - Timestamp: when form was opened
   * - Token: JavaScript-generated token
   * - Honeypot: will remain empty (hidden field)
   */
  initSpamProtection() {
    // Current timestamp (Unix time)
    this.spamTimestamp = Math.floor(Date.now() / 1000);

    // Generate random token
    this.spamToken = this.generateToken();

    // Honeypot will be empty by default (added in submission)
  }

  /**
   * Generate random token for spam protection
   */
  generateToken() {
    const array = new Uint8Array(16);
    crypto.getRandomValues(array);
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
  }

  open() {
    // Reset state
    this.currentStep = 0;
    this.userData = {};
    this.isProcessing = false;
    this.chatMessages.innerHTML = '';
    this.chatInputArea.classList.add('hidden');

    // Initialize spam protection fields
    this.initSpamProtection();

    // Show modal
    this.modal.classList.remove('hidden');

    // Trigger animations
    requestAnimationFrame(() => {
      this.modalBackdrop.classList.remove('opacity-0');
      this.modalBackdrop.classList.add('opacity-100');
      this.modalContent.classList.remove('translate-x-full');
      this.modalContent.classList.add('translate-x-0');
    });

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    // Start conversation
    setTimeout(() => {
      this.askQuestion();
    }, 600);
  }

  close() {
    // Trigger exit animations
    this.modalBackdrop.classList.remove('opacity-100');
    this.modalBackdrop.classList.add('opacity-0');
    this.modalContent.classList.remove('translate-x-0');
    this.modalContent.classList.add('translate-x-full');

    // Hide modal after animation
    setTimeout(() => {
      this.modal.classList.add('hidden');
      document.body.style.overflow = '';
    }, 500);
  }

  async askQuestion() {
    if (this.isProcessing) return;
    this.isProcessing = true;

    const question = this.questions[this.currentStep];
    if (!question) {
      await this.submitForm();
      return;
    }

    // Update progress
    this.updateProgress();

    // Show typing indicator
    this.showTypingIndicator();
    await this.delay(1000);
    this.hideTypingIndicator();

    // Get question text
    const questionText = typeof question.questionText === 'function'
      ? question.questionText(this.userData)
      : question.questionText;

    // Add bot message
    this.addBotMessage(questionText);
    await this.delay(400);

    // Show appropriate input method
    if (question.fieldType === 'buttons') {
      this.showQuickReplies(question.options);
    } else {
      this.showTextInput(question);
    }

    this.isProcessing = false;
  }

  addBotMessage(text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatflow-bot-message';

    const avatarHtml = this.showInitials
      ? `<span style="color: ${this.initialsColor}; font-weight: 600; font-size: 16px;">${this.initials}</span>`
      : '';

    messageDiv.innerHTML = `
      <div class="chatflow-avatar-container" style="${this.avatarStyle || ''}">${avatarHtml}</div>
      <div class="chatflow-message-bubble">
        <p>${this.escapeHtml(text)}</p>
      </div>
    `;
    this.chatMessages.appendChild(messageDiv);
    this.scrollToBottom();

    requestAnimationFrame(() => {
      messageDiv.classList.add('chatflow-message-visible');
    });
  }

  addUserMessage(text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatflow-user-message';
    messageDiv.innerHTML = `
      <div class="chatflow-user-bubble">
        <p>${this.escapeHtml(text)}</p>
      </div>
    `;
    this.chatMessages.appendChild(messageDiv);
    this.scrollToBottom();

    requestAnimationFrame(() => {
      messageDiv.classList.add('chatflow-message-visible');
    });
  }

  showTypingIndicator() {
    this.typingIndicatorElement = document.createElement('div');
    this.typingIndicatorElement.className = 'chatflow-bot-message';

    const avatarHtml = this.showInitials
      ? `<span style="color: ${this.initialsColor}; font-weight: 600; font-size: 16px;">${this.initials}</span>`
      : '';

    this.typingIndicatorElement.innerHTML = `
      <div class="chatflow-avatar-container" style="${this.avatarStyle || ''}">${avatarHtml}</div>
      <div class="chatflow-message-bubble">
        <div class="chatflow-typing-dots">
          <div class="chatflow-dot" style="animation-delay: 0ms;"></div>
          <div class="chatflow-dot" style="animation-delay: 150ms;"></div>
          <div class="chatflow-dot" style="animation-delay: 300ms;"></div>
        </div>
      </div>
    `;
    this.chatMessages.appendChild(this.typingIndicatorElement);
    this.scrollToBottom();

    requestAnimationFrame(() => {
      this.typingIndicatorElement.classList.add('chatflow-message-visible');
    });
  }

  hideTypingIndicator() {
    if (this.typingIndicatorElement) {
      this.typingIndicatorElement.remove();
      this.typingIndicatorElement = null;
    }
  }

  showTextInput(question) {
    this.chatInputArea.classList.remove('hidden');
    this.textInputContainer.classList.remove('hidden');
    this.quickRepliesContainer.classList.add('hidden');

    // Set input type and placeholder
    this.chatTextInput.type = question.fieldType === 'email' ? 'email' :
                              question.fieldType === 'tel' ? 'tel' :
                              question.fieldType === 'date' ? 'date' : 'text';
    this.chatTextInput.placeholder = question.placeholder || this.translations.typeAnswer;
    this.chatTextInput.value = '';
    this.chatTextInput.focus();

    // Add skip button for optional questions
    if (!question.required) {
      const skipText = question.skipText || this.translations.skipQuestion;
      this.addSkipButton(skipText);
    }
  }

  showQuickReplies(options) {
    this.chatInputArea.classList.remove('hidden');
    this.quickRepliesContainer.classList.remove('hidden');
    this.textInputContainer.classList.add('hidden');

    // Clear previous buttons
    this.quickReplies.innerHTML = '';

    // Create buttons
    options.forEach(option => {
      const button = document.createElement('button');
      button.type = 'button';

      // Support both string arrays and object arrays
      const label = typeof option === 'string' ? option : option.label;
      const value = typeof option === 'string' ? option : (option.value || option.label);

      button.textContent = label;
      button.addEventListener('click', () => {
        this.handleButtonClick(label, value);
      });
      this.quickReplies.appendChild(button);
    });
  }

  addSkipButton(skipText) {
    const skipButton = document.createElement('button');
    skipButton.type = 'button';
    skipButton.className = 'chatflow-skip-btn';
    skipButton.textContent = skipText;
    skipButton.addEventListener('click', () => {
      this.handleSkip(skipText);
    });
    this.textInputContainer.appendChild(skipButton);
  }

  async handleTextInput() {
    const value = this.chatTextInput.value.trim();
    const question = this.questions[this.currentStep];

    if (!value && question.required) {
      this.shakeInput();
      return;
    }

    // Validate email
    if (question.fieldType === 'email' && value && !this.validateEmail(value)) {
      this.shakeInput();
      return;
    }

    // Hide input
    this.chatInputArea.classList.add('hidden');

    // Remove skip button
    const skipButton = this.textInputContainer.querySelector('.chatflow-skip-btn');
    if (skipButton) skipButton.remove();

    // Add user message
    if (value) {
      this.addUserMessage(value);
      this.userData[question.fieldName] = value;
    }

    // Next step
    await this.delay(600);
    this.currentStep++;
    this.askQuestion();
  }

  async handleButtonClick(label, value) {
    const question = this.questions[this.currentStep];

    // Hide input
    this.chatInputArea.classList.add('hidden');

    // Add user message
    this.addUserMessage(label);
    this.userData[question.fieldName] = value;

    // Next step
    await this.delay(600);
    this.currentStep++;
    this.askQuestion();
  }

  async handleSkip(skipText) {
    const question = this.questions[this.currentStep];

    // Hide input
    this.chatInputArea.classList.add('hidden');

    // Remove skip button
    const skipButton = this.textInputContainer.querySelector('.chatflow-skip-btn');
    if (skipButton) skipButton.remove();

    // Add user message
    this.addUserMessage(skipText);
    this.userData[question.fieldName] = '';

    // Next step
    await this.delay(600);
    this.currentStep++;
    this.askQuestion();
  }

  async submitForm() {
    // Show typing indicator
    this.showTypingIndicator();
    await this.delay(1000);
    this.hideTypingIndicator();

    try {
      // Prepare submission data with spam protection fields
      const submissionData = {
        ...this.userData,
        _chatflow_website: '', // Honeypot (should remain empty)
        _chatflow_timestamp: this.spamTimestamp,
        _chatflow_token: this.spamToken
      };

      // Submit to server
      const response = await fetch('/actions/chatflow/submit/submit', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          formHandle: this.formHandle,
          data: submissionData,
          [window.Craft.csrfTokenName]: window.Craft.csrfTokenValue,
        }),
      });

      const result = await response.json();

      if (result.success) {
        // Show success message
        this.addBotMessage(result.message || this.successMessage);

        // Callback
        if (this.onComplete) {
          this.onComplete(this.userData);
        }

        // Close after delay, then reset
        await this.delay(2000);
        this.close();

        // Reset after close animation completes
        setTimeout(() => {
          this.reset();
        }, 500);
      } else {
        // Show error
        this.addBotMessage(this.translations.errorGeneric);

        if (this.onError) {
          this.onError(result.errors || {});
        }
      }
    } catch (error) {
      console.error('ChatFlow submit error:', error);
      this.addBotMessage(this.translations.errorNetwork);

      if (this.onError) {
        this.onError(error);
      }
    }
  }

  updateProgress() {
    const totalSteps = this.questions.length;
    const currentStep = this.currentStep + 1;
    this.chatProgress.textContent = this.translations.stepProgress
      .replace('{current}', currentStep)
      .replace('{total}', totalSteps);

    // Update progress bars
    this.progressBars.forEach((bar, index) => {
      if (index < currentStep) {
        bar.classList.remove('bg-zinc-200');
        bar.classList.add('bg-purple');
      } else {
        bar.classList.remove('bg-purple');
        bar.classList.add('bg-zinc-200');
      }
    });
  }

  scrollToBottom() {
    requestAnimationFrame(() => {
      this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
    });
  }

  shakeInput() {
    this.chatTextInput.classList.add('animate-shake');
    setTimeout(() => {
      this.chatTextInput.classList.remove('animate-shake');
    }, 500);
  }

  validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  reset() {
    // Reset state (ready for next open)
    this.currentStep = 0;
    this.userData = {};
    this.isProcessing = false;
    this.chatMessages.innerHTML = '';
    this.chatInputArea.classList.add('hidden');
  }
}

// Make ChatFlow globally available
window.ChatFlow = ChatFlow;
