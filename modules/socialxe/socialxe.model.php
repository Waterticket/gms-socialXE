<?php
	/**
	 * @class  socialxeModel
	 * @author CONORY (https://xe.conory.com)
	 * @brief The model class fo the socialxe module
	 */
	class socialxeModel extends socialxe
	{

		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
		/**
		 * @brief 사용 가능한 엑세스 토큰 넣기
		 */
		function setAvailableAccessToken(&$oLibrary, $sns_info, $db = true)
		{
			// 새로고침 토큰이 없을 경우 그대로 넣기
			if(!$sns_info->refresh_token)
			{
				$oLibrary->setAccessToken($sns_info->access_token);
				
				return;
			}
			
			// 토큰 새로고침
			$oLibrary->setRefreshToken($sns_info->refresh_token);
			$oLibrary->refreshToken();
			
			// [실패] 이전 토큰 그대로 넣기
			if(!$oLibrary->getAccessToken())
			{
				$oLibrary->setAccessToken($sns_info->access_token);
			}
			// [성공] 새로고침된 토큰을 DB에 저장
			else if($db)
			{
				$args = new stdClass;
				$args->refresh_token = $oLibrary->getRefreshToken();
				$args->access_token = $oLibrary->getAccessToken();
				$args->service = $oLibrary->getService();
				$args->member_srl = $sns_info->member_srl;
				
				executeQuery('socialxe.updateMemberSns', $args);
			}
		}
		
		/**
		 * @brief 회원 SNS
		 */
		function getMemberSns($service = null, $member_srl = null)
		{
			if(!$member_srl)
			{
				if(!Context::get('is_logged'))
				{
					return;
				}
				
				$member_srl = Context::get('logged_info')->member_srl;
			}
			
			$args = new stdClass;
			$args->service = $service;
			$args->member_srl = $member_srl;
			
            return executeQuery('socialxe.getMemberSns', $args)->data;
		}
		
		/**
		 * @brief SNS ID로 회원조회
		 */
		function getMemberSnsById($id, $service = null)
		{
			$args = new stdClass;
			$args->id = $id;
			$args->service = $service;
			
            return executeQuery('socialxe.getMemberSns', $args)->data;
		}
		
		/**
		 * @brief SNS ID 첫 로그인 조회
		 */
		function getSnsUser($id, $service = null)
		{
			$args = new stdClass;
			$args->id = $id;
			$args->service = $service;
			
            return executeQuery('socialxe.getSnsUser', $args)->data;
		}
		
		/**
		 * @brief SNS 유저여부
		 */
		function memberUserSns($member_srl = null)
		{
			$sns_list = $this->getMemberSns(null, $member_srl);
			
			if(!is_array($sns_list))
			{
				$sns_list = array($sns_list);
			}
			
			if(count($sns_list) > 0)
			{
				return true;
			}
			
            return false;
		}
		
		/**
		 * @brief 기존 유저여부 (가입일과 SNS 등록일이 같다면)
		 */
		function memberUserPrev($member_srl = null)
		{
			if(!$member_srl)
			{
				if(!Context::get('is_logged'))
				{
					return;
				}
				
				$member_srl = Context::get('logged_info')->member_srl;
			}
			
			$member_info = getModel('member')->getMemberInfoByMemberSrl($member_srl);
			
			$args = new stdClass;
			$args->regdate_less = date('YmdHis', strtotime(sprintf('%s +1 minute', $member_info->regdate)));	
			$args->member_srl = $member_srl;
			
			if(!executeQuery('socialxe.getMemberSns', $args)->data)
			{
				return true;
			}
			
            return false;
		}
		
		/**
		 * @brief SNS 인증 URL
		 */
		function snsAuthUrl($service, $type)
		{
            return getUrl(
				'',
				'mid', Context::get('mid'),
				'act', 'dispSocialxeConnectSns',
				'service', $service,
				'type', $type,
				'redirect', $_SERVER['QUERY_STRING']
			);
		}

        /**
         * @brief 쿠키 SNS 전용 인증 URL
         */
        function snsAuthUrl_forck_social($service, $type)
        {
            return getUrl(
                '',
                'mid', Context::get('mid'),
                'act', 'dispSocialxeConnectSns',
                'service', $service,
                'type', $type
            );
        }
		
 		/**
		 *@brief 로그기록
		 **/
        function logRecord($act, $info = null)
		{
			if(!is_object($info))
			{
				$info = Context::getRequestVars();
			}
			
			$args = new stdClass;
			
			switch($act)
			{
				case 'procSocialxeConfirmMail' : 
					$args->category = 'register';
					$args->content = sprintf('첫 로그인 이메일 주소 등록 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
					break;
					
				case 'procSocialxeInputAddInfo' : 
					$args->category = 'register';
					$info->msg = $info->msg ?: '로그인 성공';
					$args->content = sprintf('추가정보 입력 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
					break;
					
				case 'procSocialxeSnsClear' : 
					$args->category = 'sns_clear';
					$args->content = sprintf('SNS 연결 해제 (SNS : %s)', $info->sns);
					break;
					
				case 'procSocialxeSnsLinkage' : 
					$args->category = 'linkage';
					$args->content = sprintf('SNS 연동설정 변경 (SNS : %s, 변경값 : %s)', $info->sns, $info->linkage);
					break;
					
				case 'dispSocialxeConnectSns' : 
					$args->category = 'auth_request';
					$args->content = sprintf('SNS 인증 요청 (SNS : %s)', $info->sns);
					break;
					
				case 'procSocialxeCallback' : 
					$args->category = $info->type;
					
					if($info->type == 'register')
					{
						$info->msg = $info->msg ?: '등록 성공';
						$args->content = sprintf('SNS 등록 실행 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
					}
					else if($info->type == 'login')
					{
						$info->msg = $info->msg ?: '로그인 성공';
						$args->content = sprintf('SNS 로그인 실행 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
					}
					
					break;
					
				case 'linkage' : 
					$args->category = 'linkage';
					$args->content = sprintf('SNS 연동 (게시물 전송) (SNS : %s, Title : %s)', $info->sns, $info->title);
					break;
					
				case 'delete_member' : 
					$args->category = 'delete_member';
					
					if($info->nick_name)
					{
						$args->content = sprintf('회원정보 삭제 (탈퇴) (회원번호 : %s, 닉네임 : %s, SNS ID : %s)', $info->member_srl, $info->nick_name, $info->sns_id);
					}
					else
					{
						$args->content = sprintf('[자동실행] 인증메일 유효시간이 지나 회원정보 삭제 (탈퇴) (회원번호 : %s, SNS ID : %s)', $info->member_srl, $info->nick_name, $info->sns_id);
					}
					
					break;
			}
			
			if(!$args->category)
			{
				$args->category = 'unknown';
				$args->content = sprintf('%s (act : %s)', Context::getLang('unknown'), $act);
			}
			
            $args->act = $act;
			$args->micro_time = microtime(true);
			$args->member_srl = Context::get('logged_info')->member_srl;
			
            executeQuery('socialxe.insertLogRecord', $args);
        }
	}