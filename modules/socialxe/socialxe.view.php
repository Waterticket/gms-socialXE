<?php
	/**
	 * @class  socialxeView
	 * @author CONORY (https://xe.conory.com)
	 * @brief The view class of the socialxe module
	 */
	class socialxeView extends socialxe
	{
		/**
		 * @brief Initialization
		 */
        function init() 
		{
			Context::set('config', $this->config);
			
            $this->setTemplatePath(sprintf('%sskins/%s/', $this->module_path, $this->config->skin));
			
            Context::addJsFile($this->module_path . 'tpl/js/socialxe.js');
			
			// ����� ���̾ƿ�
			if($this->config->layout_srl && $layout_path = getModel('layout')->getLayout($this->config->layout_srl)->path)
			{
				$this->module_info->layout_srl = $this->config->layout_srl;
				
				$this->setLayoutPath($layout_path);
			}
        }

		/**
		 * @brief SNS ����
		 */
		function dispSocialxeSnsManage()
		{
            if(!Context::get('is_logged'))
			{
				return new BaseObject(-1, 'msg_not_logged');
			}
			
			$oSocialxeModel = getModel('socialxe');
			
			foreach($this->config->sns_services as $key => $val)
			{
				$args = new stdClass;
				
				if(($sns_info = $oSocialxeModel->getMemberSns($val)) && $sns_info->name)
				{
					$args->register = true;
					$args->sns_status = sprintf('<a href="%s" target="_blank">%s</a>',$sns_info->profile_url, $sns_info->name);
				}
				else
				{
					$args->auth_url = $oSocialxeModel->snsAuthUrl($val, 'register');
					$args->sns_status = Context::getLang('status_sns_no_register');
				}
				
				$args->service = $val;
				$args->linkage = $sns_info->linkage;
				
				$sns_services[$key] = $args;
			}
			
			Context::set('sns_services', $sns_services);
			
			$this->setTemplateFile('sns_manage');
		}
		
		/**
		 * @brief �̸��� Ȯ��
		 */
		function dispSocialxeConfirmMail()
		{
			if(!$_SESSION['tmp_socialxe_confirm_email'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			Context::set('service', $_SESSION['tmp_socialxe_confirm_email']['service']);
			
			$_SESSION['socialxe_confirm_email'] = $_SESSION['tmp_socialxe_confirm_email'];
			
			unset($_SESSION['tmp_socialxe_confirm_email']);
			
			$this->setTemplateFile('confirm_email');
		}
		
		/**
		 * @brief �߰����� �Է�
		 */
		function dispSocialxeInputAddInfo()
		{
			if(!$_SESSION['tmp_socialxe_input_add_info'])
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			$_SESSION['socialxe_input_add_info'] = $_SESSION['tmp_socialxe_input_add_info'];
			
			unset($_SESSION['tmp_socialxe_input_add_info']);
			
			$member_config = getModel('member')->getMemberConfig();
			
			Context::set('member_config', $member_config);
			Context::set('nick_name', $_SESSION['socialxe_input_add_info']['nick_name']);
			
			$signupForm = array();
			
			// �ʼ� �߰� ������ ���
			if(in_array('require_add_info', $this->config->sns_input_add_info))
			{
				foreach($member_config->signupForm as $no => $formInfo)
				{
					if(!$formInfo->required || $formInfo->isDefaultForm)
					{
						continue;
					}
					
					$signupForm[] = $formInfo;
				}
				
				$member_config->signupForm = $signupForm;
				
				$oMemberAdminView = getAdminView('member');
				$oMemberAdminView->memberConfig = $member_config;
				
				Context::set('formTags', $oMemberAdminView->_getMemberInputTag());
				
				getView('member')->addExtraFormValidatorMessage();
			}
			
			// ���̵� ��
			if(in_array('user_id', $this->config->sns_input_add_info))
			{
				$args = new stdClass;
				$args->required = true;
				$args->name = 'user_id';
				$signupForm[] = $args;
			}
			
			// �г��� ��
			if(in_array('nick_name', $this->config->sns_input_add_info))
			{
				$args = new stdClass;
				$args->required = true;
				$args->name = 'nick_name';
				$signupForm[] = $args;
			}
			
			// ��� ����
			$this->_createAddInfoRuleset($signupForm, in_array('agreement', $this->config->sns_input_add_info));
			
			$this->setTemplateFile('input_add_info');
		}
		
		/**
		 * @brief SNS ���� ����
		 */
		function dispSocialxeConnectSns()
		{
			if(isCrawler())
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!($service = Context::get('service')) || !in_array($service, $this->config->sns_services))
			{
				return new BaseObject(-1, 'msg_not_support_service_login');
			}
			
			if(!$oLibrary = $this->getLibrary($service))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!$type = Context::get('type'))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if($type == 'register' && !Context::get('is_logged'))
			{
				return new BaseObject(-1, 'msg_not_logged');
			}
			else if($type == 'login' && Context::get('is_logged'))
			{
				return new BaseObject(-1, 'already_logged');
			}
			
			// ���� ���� ��ȿ �ð�
			if($this->config->mail_auth_valid_hour)
			{
				$args = new stdClass;
				$args->list_count = 5;
				$args->regdate_less = date('YmdHis', strtotime(sprintf('-%s hour', $this->config->mail_auth_valid_hour)));
				$output = executeQueryArray('socialxe.getAuthMailLess', $args);
				
				if($output->toBool())
				{
					$oMemberController = getController('member');
					
					foreach($output->data as $key => $val)
					{
						if(!$val->member_srl)
						{
							continue;
						}
						
						$oMemberController->deleteMember($val->member_srl);
					}
				}
			}
			
			unset($_SESSION['socialxe_input_add_info_data']);
			
			$_SESSION['socialxe_auth']['type'] = $type;
			$_SESSION['socialxe_auth']['mid'] = Context::get('mid');
			$_SESSION['socialxe_auth']['redirect'] = Context::get('redirect');
			$_SESSION['socialxe_auth']['state'] = md5(microtime() . mt_rand());
			$_SESSION['socialxe_auth']['app'] = Context::get('rdset');

			$this->setRedirectUrl($oLibrary->createAuthUrl($type));
			
			// �α� ���
			$info = new stdClass;
			$info->sns = $service;
			$info->type = $type;
			getModel('socialxe')->logRecord($this->act, $info);
		}
		
		/**
		 * @brief SNS ������
		 */
		function dispSocialxeSnsProfile()
		{
			if($this->config->sns_profile != 'Y')
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!Context::get('member_srl'))
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			if(!($member_info = getModel('member')->getMemberInfoByMemberSrl(Context::get('member_srl'))) || !$member_info->member_srl)
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}
			
			Context::set('member_info', $member_info);
			
			foreach($this->config->sns_services as $key => $val)
			{
				if(!($sns_info = getModel('socialxe')->getMemberSns($val, $member_info->member_srl)) || !$sns_info->name)
				{
					continue;
				}
				
				$args = new stdClass;
				$args->profile_name = $sns_info->name;
				$args->profile_url = $sns_info->profile_url;
				$args->service = $val;
				
				$sns_services[$key] = $args;
			}
			
			Context::set('sns_services', $sns_services);
			
			$this->setTemplateFile('sns_profile');
		}
		
		/**
		 * @brief �ʼ� �߰��� ��� ���� ����
		 */
		function _createAddInfoRuleset($signupForm, $agreement = false)
		{
			$xml_file = 'files/ruleset/insertAddInfoSocialxe.xml';
			
			$buff = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL.
				'<ruleset version="1.5.0">' . PHP_EOL.
				'<customrules>' . PHP_EOL.
				'</customrules>' . PHP_EOL.
				'<fields>' . PHP_EOL . '%s' . PHP_EOL . '</fields>' . PHP_EOL.
				'</ruleset>';
			
			$fields = array();
			
			if($agreement)
			{
				$fields[] = '<field name="accept_agreement" required="true" />';
			}
			
			foreach($signupForm as $formInfo)
			{
				if($formInfo->required || $formInfo->mustRequired)
				{
					if($formInfo->type == 'tel' || $formInfo->type == 'kr_zip')
					{
						$fields[] = sprintf('<field name="%s[]" required="true" />', $formInfo->name);
					}
					else if($formInfo->name == 'nick_name')
					{
						$fields[] = sprintf('<field name="%s" required="true" length="2:20" />', $formInfo->name);
					}
					else
					{
						$fields[] = sprintf('<field name="%s" required="true" />', $formInfo->name);
					}
				}
			}
			
			FileHandler::writeFile($xml_file, sprintf($buff, implode(PHP_EOL, $fields)));
			
			$validator = new Validator($xml_file);
			$validator->setCacheDir('files/cache');
			$validator->getJsPath();
		}
	}