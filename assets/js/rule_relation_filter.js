document.addEventListener('DOMContentLoaded', (event) => {
    $(function() {
        // Écouter les événements d'entrée sur les champs de formulaire
        $('#relation_filter_field, #relation_filter_another_field').on('change', checkInputs);
        $('#relation_filter_textarea_field').on('input', checkInputs);

        function checkInputs() {
            var fieldInput = $('#relation_filter_field').val();
            var anotherFieldInput = $('#relation_filter_another_field').val();
            var textareaFieldInput = $('#relation_filter_textarea_field').val();
        
            if (fieldInput && anotherFieldInput && textareaFieldInput) {
                $('#myButton').prop('disabled', false);
            } else {
                $('#myButton').prop('disabled', true);
            }
        }

        $('#myButton').on('click', function() {
    
            var fieldInput =$('#relation_filter_field option:selected').text();
            var textareaFieldInput = $('#relation_filter_textarea_field').val();
            
            // Get the value of the selected option in relation_filter_another_field
            var anotherFieldInputVal = $('#relation_filter_another_field').val();
            var anotherFieldInputText = $('#relation_filter_another_field option:selected').text();
        
            // Create new list item
            let newItem = $(`
                <li class="mt-2 d-flex justify-content-evenly align-items-baseline">
                    <span class="name me-2 mt-2">${fieldInput}</span> 
                    <a class="fancybox me-2" data-fancybox-type="iframe" href="/index.php/rule/info/source/"> 
                        <svg class="svg-inline--fa fa-question-circle fa-w-16" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="question-circle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                            <path fill="currentColor" d="M504 256c0 136.997-111.043 248-248 248S8 392.997 8 256C8 119.083 119.043 8 256 8s248 111.083 248 248zM262.655 90c-54.497 0-89.255 22.957-116.549 63.758-3.536 5.286-2.353 12.415 2.715 16.258l34.699 26.31c5.205 3.947 12.621 3.008 16.665-2.122 17.864-22.658 30.113-35.797 57.303-35.797 20.429 0 45.698 13.148 45.698 32.958 0 14.976-12.363 22.667-32.534 33.976C247.128 238.528 216 254.941 216 296v4c0 6.627 5.373 12 12 12h56c6.627 0 12-5.373 12-12v-1.333c0-28.462 83.186-29.647 83.186-106.667 0-58.002-60.165-102-116.531-102zM256 338c-25.365 0-46 20.635-46 46 0 25.364 20.635 46 46 46s46-20.636 46-46c0-25.365-20.635-46-46-46z"></path>
                        </svg>
                    </a>
                    <input type="text" name="fieldsfilter[]['anotherFieldInput']" value="${anotherFieldInputVal}" class="form-control filter-input my-3">
                    <input type="text" name="fieldsfilter[]['textareaFieldInput']" value="${textareaFieldInput}" class="form-control filter-input my-3">
                    <button class="btn btn-danger remove-button"> <i class="fa fa-times " aria-hidden="true"></i></button>
                </li>
            `);
            $('#fieldsfilter').append(newItem);
            
            // Clear form fields
            $('#relation_filter_field').val('');
            $('#relation_filter_another_field').val('');
            $('#relation_filter_textarea_field').val('');
        
            $('#myButton').prop('disabled', true);
            // Add click event to the remove button
            newItem.find('.remove-button').click(function() {
                $(this).parent().remove();
            });
        });
        
    });
});
