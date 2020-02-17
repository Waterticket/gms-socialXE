<?php
	/**
	 * @class  socialxe
     * @author CONORY (https://xe.conory.com)
	 * @brief The parent class of the socialxe module
	 */
	
	class socialxe extends ModuleObject
	{
		public $config;
		
		public $default_services = array(
			'twitter',
			'facebook',
			'google',
			'naver',
			'kakao',
		);
		
		private $library = array();
		
		private $triggers = array(
			array('moduleHandler.init', 'socialxe', 'controller', 'triggerModuleHandler', 'after'),
			array('moduleObject.proc', 'socialxe', 'controller', 'triggerModuleObjectBefore', 'before'),
			array('moduleObject.proc', 'socialxe', 'controller', 'triggerModuleObjectAfter', 'after'),
			array('display', 'socialxe', 'controller', 'triggerDisplay', 'before'),
			array('document.insertDocument', 'socialxe', 'controller', 'triggerInsertDocumentAfter', 'after'),
			array('member.procMemberInsert', 'socialxe', 'controller', 'triggerInsertMember', 'before'),
			array('member.getMemberMenu', 'socialxe', 'controller', 'triggerMemberMenu', 'after'),
			array('member.deleteMember', 'socialxe', 'controller', 'triggerDeleteMember', 'after'),
		);
		
		/**
		 * @brief Constructor
		 */
		function __construct()
		{
			$this->config = $this->getConfig();
			
			if(!Context::isExistsSSLAction('procSocialxeCallback') && Context::getSslStatus() == 'optional')
			{
				Context::addSSLActions(array(
					'dispSocialxeConfirmMail',
					'procSocialxeConfirmMail',
					'procSocialxeCallback',
					'dispSocialxeConnectSns',
				));
			}
		}
		
		/**
		 * @brief 모듈 설치
		 */
		function moduleInstall()
		{
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			return new BaseObject();
		}

		/**
		 * @brief 업데이트 체크
		 */
		function checkUpdate()
		{
            $oDB = DB::getInstance();
            $oModuleModel = getModel('module');	
			
			// 트리거 설치
			foreach($this->triggers as $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					return true;
				}
			}
			
			return false;
		}

		/**
		 * @brief 업데이트
		 */
		function moduleUpdate()
		{
            $oDB = DB::getInstance();
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			// 트리거 설치
			foreach($this->triggers as $trigger)
			{
				if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
				}
			}
			
			return new BaseObject(0, 'success_updated');
		}
		
		/**
		 * @brief 모듈 삭제
		 */
		function moduleUninstall()
		{
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			// 트리거 삭제
			foreach($this->triggers as $trigger)
			{
				if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
				{
					$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
				}
			}
			
			return new BaseObject();
		}
		
		/**
		 * @brief 캐시파일 재생성
		 */
		function recompileCache()
		{
		}
		
 		/**
		 *@brief 설정
		 **/
        function getConfig() 
		{
			$config = getModel('module')->getModuleConfig('socialxe');
			
			if(!$config->delete_auto_log_record)
			{
				$config->delete_auto_log_record = 0;
			}
			
			if(!$config->skin)
			{
				$config->skin = 'default';
			}
			
			if(!$config->mskin)
			{
				$config->mskin = 'default';
			}
			
			if(!$config->sns_follower_count)
			{
				$config->sns_follower_count = 0;
			}
			
			if(!$config->mail_auth_valid_hour)
			{
				$config->mail_auth_valid_hour = 0;
			}
			
			if(!$config->sns_services)
			{
				$config->sns_services = $this->default_services;
			}
			
            return $config;
        }
		
 		/**
		 *@brief socialxe library
		 **/
        function getLibrary($library_name) 
		{
			require_once('modules/socialxe/socialxe.library.php');
			
			if(!isset($this->library[$library_name]))
			{
				if(($library_file = sprintf('modules/socialxe/libs/%s.lib.php', $library_name)) && !file_exists($library_file))
				{
					return;
				}
				
				require_once($library_file);
				
				if(($instance_name = sprintf('library%s', ucwords($library_name))) && !class_exists($instance_name, false))
				{
					return;
				}
				
				$this->library[$library_name] = new $instance_name($library_name);
			}
			
            return $this->library[$library_name];
        }
	}