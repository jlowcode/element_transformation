/*! PITT */

define(["jquery"], function (n, i) {
	jQuery('.fabrikForm').append('<div div class="modal fade" id="mdElementTransformation" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="myModalLabel">' +
		'<div class="modal-dialog modal-sm" role="document">' +
		'<div class="modal-content">' +
		'<div class="modal-body text-center">' +
		'<img class="img-responsive" src="../plugins/fabrik_list/element_transformation/img/carregando.gif">' +
		'</div>' +
		'</div>' +
		'</div>' +
		'</div>');

	jQuery('#mdElementTransformation').modal('show');
});