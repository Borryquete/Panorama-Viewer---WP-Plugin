jQuery(document).ready(function($) {
    // Subir imagen panorámica
    $('#upload_image_button').click(function(e) {
        e.preventDefault();
        var button = $(this);
        var field = button.prev();
        var customUploader = wp.media({
            title: 'Selecciona una imagen',
            button: { text: 'Usar esta imagen' },
            multiple: false
        }).on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            field.val(attachment.url);
        }).open();
    });

    // Subir imagen de vista previa
    $('#upload_preview_button').click(function(e) {
        e.preventDefault();
        var button = $(this);
        var field = button.prev();
        var customUploader = wp.media({
            title: 'Selecciona una imagen',
            button: { text: 'Usar esta imagen' },
            multiple: false
        }).on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            field.val(attachment.url);
        }).open();
    });
});
