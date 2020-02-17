/**
 * @file   tpl/js/socialxe.js
 * @author CONORY (https://www.conory.com)
 * @brief  socialxe 모듈의 javascript
 **/

/* SNS 해제 */
function clearSns(service)
{
	get_by_id('service1').value = service;
    var dF = get_by_id('clearForm');
	dF.submit();
}

/* SNS 연동 */
function linkageSns(service)
{
	get_by_id('service2').value = service;
    var dF = get_by_id('linkageForm');
	dF.submit();
}