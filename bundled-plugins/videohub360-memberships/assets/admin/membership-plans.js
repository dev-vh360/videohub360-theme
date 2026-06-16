(function(){
    document.addEventListener('click', function(event){
        var deleteButton = event.target.closest('[data-vh360-delete-plan]');
        if (deleteButton && !window.confirm(deleteButton.getAttribute('data-confirm') || 'Delete this plan?')) {
            event.preventDefault();
        }
    });
})();
