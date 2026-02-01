// Form Builder JavaScript
(function() {
    // Wait for DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        var questionIndex = parseInt(document.getElementById('question-index-data').value) || 0;

        // Initialize drag and drop for reordering
        initDragAndDrop();

        $('#add-question').on('click', function() {
            var html = '<div class="question-item matrixblock" data-index="' + questionIndex + '">' +
                '<div class="titlebar">' +
                    '<div class="preview"><span class="title" style="color: #999; font-style: italic;">New question - click to edit</span></div>' +
                    '<div class="actions">' +
                        '<a class="move icon" title="Reorder"></a>' +
                        '<a class="settings icon question-toggle" title="Edit"></a>' +
                        '<a class="delete icon" title="Delete"></a>' +
                    '</div>' +
                '</div>' +
                '<div class="question-fields" style="display: none;">' +
                    '<div class="field"><div class="heading"><label>Question Text</label></div>' +
                    '<div class="input"><input type="text" name="questions[' + questionIndex + '][questionText]" class="text fullwidth" required></div></div>' +
                    '<div class="field"><div class="heading"><label>Field Type</label></div>' +
                    '<div class="input"><div class="select"><select name="questions[' + questionIndex + '][fieldType]" class="field-type-select">' +
                        '<option value="text">Short Text</option>' +
                        '<option value="email">Email</option>' +
                        '<option value="tel">Phone</option>' +
                        '<option value="textarea">Long Text</option>' +
                        '<option value="buttons">Multiple Choice</option>' +
                        '<option value="date">Date</option>' +
                    '</select></div></div></div>' +
                    '<div class="field"><div class="heading"><label>Field Name</label></div>' +
                    '<div class="input"><input type="text" name="questions[' + questionIndex + '][fieldName]" class="text code fullwidth" required></div></div>' +
                    '<div class="field"><div class="heading"><label>Placeholder</label></div>' +
                    '<div class="input"><input type="text" name="questions[' + questionIndex + '][placeholder]" class="text fullwidth"></div></div>' +
                    '<div class="field"><div class="heading"><label>Required</label></div>' +
                    '<div class="input"><div class="lightswitch on" tabindex="0"><div class="lightswitch-container">' +
                    '<div class="handle"></div></div>' +
                    '<input type="hidden" name="questions[' + questionIndex + '][required]" value="1"></div></div></div>' +
                    '<div class="field"><div class="heading"><label>Skip Button Text</label></div>' +
                    '<div class="input"><input type="text" name="questions[' + questionIndex + '][skipText]" class="text fullwidth" placeholder="e.g., Skip this question"></div></div>' +
                    '<div class="field options-field" style="display: none;"><div class="heading"><label>Options (one per line)</label></div>' +
                    '<div class="input"><textarea name="questions[' + questionIndex + '][options]" class="text fullwidth" rows="4" placeholder="Option 1\nOption 2\nOption 3"></textarea></div></div>' +
                '</div></div>';

            var $newQuestion = $(html);
            $('#questions-list').append($newQuestion);

            // Automatically show fields for new question
            $newQuestion.find('.question-fields').show();

            questionIndex++;
        });

        // Toggle on settings icon click
        $(document).on('click', '.question-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.question-item').find('.question-fields').slideToggle();
        });

        // Toggle on card click (titlebar)
        $(document).on('click', '.question-item .titlebar', function(e) {
            // Don't toggle if clicking on action buttons
            if ($(e.target).closest('.actions').length > 0) {
                return;
            }
            $(this).closest('.question-item').find('.question-fields').slideToggle();
        });

        $(document).on('click', '.question-item .delete', function(e) {
            e.preventDefault();
            var $item = $(this).closest('.question-item');
            var questionId = $item.find('input[name*="[id]"]').val();

            if (confirm('Are you sure you want to delete this question?')) {
                // If question has an ID, mark it for deletion instead of removing
                if (questionId) {
                    // Add hidden input to mark for deletion
                    if (!$('#deleted-questions').length) {
                        $('#questions-builder').append('<div id="deleted-questions"></div>');
                    }
                    $('#deleted-questions').append('<input type="hidden" name="deletedQuestions[]" value="' + questionId + '">');
                }

                // Remove from DOM
                $item.remove();
                updateQuestionIndexes();
            }
        });

        $(document).on('input', 'input[name*="[questionText]"]', function() {
            var $title = $(this).closest('.question-item').find('.title');
            var value = $(this).val();

            if (value) {
                $title.text(value).css({'color': '', 'font-style': ''});
            } else {
                $title.text('New question - click to edit').css({'color': '#999', 'font-style': 'italic'});
            }
        });

        $(document).on('change', '.field-type-select', function() {
            var selectedType = $(this).val();
            var questionItem = $(this).closest('.question-item');
            var optionsField = questionItem.find('.options-field');

            if (selectedType === 'buttons') {
                optionsField.show();
            } else {
                optionsField.hide();
            }
        });

        // Handle lightswitch toggle for dynamically added questions
        $(document).on('click', '.question-fields .lightswitch', function() {
            var $switch = $(this);
            var $input = $switch.find('input[type="hidden"]');

            if ($switch.hasClass('on')) {
                $switch.removeClass('on');
                $input.val('0');
            } else {
                $switch.addClass('on');
                $input.val('1');
            }
        });
    }

    function initDragAndDrop() {
        var draggedElement = null;
        var placeholder = null;

        // Make question items draggable via move handle
        $(document).on('mousedown', '.question-item .move', function(e) {
            var $item = $(this).closest('.question-item');
            $item.attr('draggable', 'true');
        });

        $(document).on('dragstart', '.question-item', function(e) {
            draggedElement = this;
            $(this).addClass('dragging');

            // Create placeholder
            placeholder = $('<div class="question-placeholder"></div>');
            placeholder.height($(this).outerHeight());
        });

        $(document).on('dragend', '.question-item', function(e) {
            $(this).removeClass('dragging');
            $(this).attr('draggable', 'false');
            if (placeholder) {
                placeholder.remove();
                placeholder = null;
            }
            draggedElement = null;
            updateQuestionIndexes();
        });

        $(document).on('dragover', '.question-item', function(e) {
            e.preventDefault();
            if (!draggedElement || draggedElement === this) return;

            var $this = $(this);
            var rect = this.getBoundingClientRect();
            var midpoint = rect.top + rect.height / 2;

            if (e.originalEvent.clientY < midpoint) {
                $this.before(draggedElement);
            } else {
                $this.after(draggedElement);
            }
        });

        $(document).on('drop', '.question-item', function(e) {
            e.preventDefault();
        });
    }

    function updateQuestionIndexes() {
        $('#questions-list .question-item').each(function(index) {
            $(this).attr('data-index', index);
            // Update field names to match new order
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name && name.indexOf('[') > -1) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                }
            });
        });
    }
})();
