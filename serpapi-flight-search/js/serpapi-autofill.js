jQuery(function($){
  function attach(selector){
    $(selector).on('input',function(){
      var q=$(this).val(),inp=$(this),grp=inp.closest('.form-group');
      if(q.length<2)return;
      $.post(serpapi_ajax.ajax_url,{action:'serpapi_lookup_airports',query:q},function(resp){
        grp.find('.autocomplete-results').remove();
        if(resp.success&&resp.data.length){
          var ul=$('<ul class="autocomplete-results"></ul>');
          resp.data.forEach(function(item){
            ul.append($('<li></li>').text(item.label).data('iata',item.value));
          });
          grp.append(ul);
          ul.on('click','li',function(){
            inp.val($(this).data('iata'));ul.remove();
          });
        }
      });
    });
  }
  attach('input[name="departure_id"]');attach('input[name="arrival_id"]');
  $(document).click(function(e){if(!$(e.target).closest('.autocomplete-results').length)$('.autocomplete-results').remove();});
});