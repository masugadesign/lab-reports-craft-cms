
function showHideFormattingFunctionField(reportType)
{
	if ( reportType == 'advanced' ) {
		$("#reportFormatFunctionContainer").show();
	} else {
		$("#reportFormatFunctionContainer").hide();
	}
}

$(document).ready(function() {
	showHideFormattingFunctionField( $("#reportType").val() );
	$("#reportType").change(function() {
		showHideFormattingFunctionField( $(this).val() );
	});
});
