import $ from 'jquery'

$(document).ready(function () {

  $('#bulk-categories input:checkbox').on('change', function () {
    var list = $('#panel-list .request .category-' + $(this).data('key') + ' input:checkbox')
    list.prop('checked', $(this).is(':checked')).change()
  })


  $('#panel-list .category-safe input:checkbox').on('change', function () {
    if ($(this).is(':checked')) {
      var list = $(this).parents('.categories').find('.category:not(.category-safe) input:checkbox')
      list.prop('checked', false).change()
    }
  })

  $('#panel-list .category-reject input:checkbox').on('change', function () {
    if ($(this).is(':checked')) {
      var list = $(this).parents('.categories').find('.category:not(.category-reject) input:checkbox')
      list.prop('checked', false).change()
    }
  })

  $('#panel-list .category:not(.category-safe,.category-reject) input:checkbox').on('change', function () {
    if ($(this).is(':checked')) {
      var list = $(this).parents('.categories').find('.category-safe,.category-reject').find('input:checkbox')
      list.prop('checked', false).change()
    }
  })

})
