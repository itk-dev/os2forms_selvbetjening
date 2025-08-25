(function ($, Drupal) {
  // Overrides splitValues() function found in core/misc/autocomplete.js
  // Danish addresses contain commas. Original splitValues() method
  // splits upon commas. Combined with default extractLastTerm this results in
  // unexpected behavior when using autocomplete with respect to danish addresses.
  // Inspired by @https://www.drupal.org/project/drupal/issues/2821181#comment-12012538.
  Drupal.autocomplete.splitValues = function (value) {
    return [ value.trim() ];
  }
})(jQuery, Drupal);
