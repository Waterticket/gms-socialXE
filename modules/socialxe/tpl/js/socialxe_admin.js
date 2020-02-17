/**
 * @file   tpl/js/socialxe_admin.js
 * @author CONORY (https://www.conory.com)
 * @brief  socialxe 모듈의 관리자용 javascript
 **/

/* 데이터 삭제 */
function deleteDate(date_srl)
{
	get_by_id('date_srl').value = date_srl;
    var dF = get_by_id('deleteForm');
	dF.submit();
}
