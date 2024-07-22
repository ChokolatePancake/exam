/**
 * @file
 * Like and unlike behavior for widget 2.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.likeit = {
    attach: function () {
      $('.likeit-widget-2').once('likeit-widget-2').each(function () {
        let $widget = $(this);
        $widget.find('.like a').click(function () {
          let url = $(this).data('url');
          let htmlId = $(this).data('id');
          likeItService.do(url, htmlId);
        });
      });
    }
  };

  window.likeItService = window.likeItService || function () {
    function likeItService () {}

    likeItService.do = function (url, htmlId) {
      $.ajax({
        type: 'POST',
        url: url + '?output=json',
        success: function success(response) {
          let {action, count, url, new_html_id} = response;
          let $wrapper = $('#' + htmlId);
          let $aTag = $wrapper.find('a');
          $aTag.data('url', url).data('id', new_html_id).attr('title', Drupal.t(action));
          if (action === 'Unlike') {
            $aTag.addClass('liked');
          } else {
            $aTag.removeClass('liked');
          }
          $aTag.find('svg.icon-like').find('title').text(Drupal.t(action));
          $wrapper.parent().find('.likeit__count .count').text(count);
          $wrapper.attr('id', new_html_id);
        }
      });
    };

    return likeItService;
  }();

})(jQuery, Drupal);
