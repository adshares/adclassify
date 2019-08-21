import jQuery from 'jquery'

(function ($) {

  $.fn.extend({
    buttonCheckbox: function (options) {
      options = $.extend({}, $.ButtonCheckbox.defaults, options)
      this.each(function () {
        new $.ButtonCheckbox(this, options)
      })
      return this
    }
  })

  // ctl is the element, options is the set of defaults + user options
  $.ButtonCheckbox = function (ctl, options) {
    var $widget = $(ctl),
      $button = $widget.find('button'),
      $checkbox = $widget.find('input:checkbox'),
      onColor = $button.data('onColor') || options.on.color,
      offColor = $button.data('offColor') || options.off.color

    // Event Handlers
    $button.on('click', function () {
      $checkbox.prop('checked', !$checkbox.is(':checked'))
      $checkbox.triggerHandler('change')
      updateDisplay()
    })
    $checkbox.on('change', function () {
      updateDisplay()
    })

    // Actions
    function updateDisplay () {
      var isChecked = $checkbox.is(':checked')

      // Set the button's state
      $button.data('state', (isChecked) ? 'on' : 'off')

      // Set the button's icon
      $button.find('.state-icon')
        .removeClass()
        .addClass('state-icon ' + options[$button.data('state')].icon)

      // Update the button's color
      if (isChecked) {
        $button
          .removeClass('btn-' + offColor)
          .addClass('btn-' + onColor + ' checked')
      } else {
        $button
          .removeClass('btn-' + onColor + ' checked')
          .addClass('btn-' + offColor)
      }
    }

    // Initialization
    function init () {
      updateDisplay()
      // Inject the icon if applicable
      if ($button.find('.state-icon').length === 0) {
        // $button.prepend('<i class="state-icon ' + options[$button.data('state')].icon + '"></i> ')
      }
    }

    init()
  }

  // option defaults
  $.ButtonCheckbox.defaults = {
    on: {
      icon: 'far fa-check-square',
      color: 'primary'
    },
    off: {
      icon: 'far fa-square',
      color: 'secondary'
    }
  }

})(jQuery)
