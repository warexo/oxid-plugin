<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>
    //$('form table tr').css('display','none');
    [{if $admfields}]
    $('form table tr > td > input, form table tr > td > select, form table tr > td > textarea').
    parent().parent().css('display','none').attr('data-hidden','true');
    $('form table tr > td > input, form table tr > td > select, form table tr > td > textarea')
        .parent().css('display','none');

    [{ foreach from=$admfields item=field}]
    $('input[name="[{$field}]"], select[name="[{$field}]"], textarea[name="[{$field}]"]').parent().parent().css('display','table-row');
    $('input[name="[{$field}]"], select[name="[{$field}]"], textarea[name="[{$field}]"]').each(function(){
        //if ($(this).parent().prop('tagName') == 'td')
        $(this).parent().css('display','table-cell');
    });

    [{/foreach}]
    var toRemove = new Array();
    $('form table tr').each(function() {
        if ($(this).css('display') == 'table-row')
            $(this).parents('[data-hidden=true]').css('display','table-row')
    });
    $('form table tr').each(function() {
        if ($(this).css('display') == 'none')
            toRemove.push($(this));
    });
    [{/if}]
    $('input[name="cl"]').val('adminwidget:'+$('input[name="cl"]').val());
    $('form').append('<input type="hidden" name="admfields" value="[{$sadmfields}]">');
</script>