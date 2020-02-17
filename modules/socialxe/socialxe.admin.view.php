<?php
	/**
	 * @class  socialxeAdminView
     * @author CONORY (https://xe.conory.com)
	 * @brief The admin view class of the socialxe module
	 */
	 
	class socialxeAdminView extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
			Context::set('config', $this->config);
			
		    // 로그 기록 자동 삭제
            if($this->module_config->delete_auto_log_record)
			{
				$args = new stdClass;
                $args->regdate_less = date('YmdHis', strtotime(sprintf('-%d day', $this->module_config->delete_auto_log_record)));
                executeQuery('socialxe.deleteLogRecordLess', $args);
            }
			
			$this->setTemplatePath($this->module_path . 'tpl');
			
			Context::addJsFile($this->module_path . 'tpl/js/socialxe_admin.js');
		}
		
		/**
		 * @brief API 설정
		 */
		function dispSocialxeAdminSettingApi()
		{
			$this->setTemplateFile('api_setting');
		}
		
		/**
		 * @brief 환경설정
		 */
		function dispSocialxeAdminSetting()
		{
			// 레이아웃 목록
			Context::set('layout_list', getModel('layout')->getLayoutList());
			Context::set('mlayout_list', getModel('layout')->getLayoutList(0, 'M'));
			
			// 스킨 목록
            Context::set('skin_list', getModel('module')->getSkins($this->module_path));
			Context::set('mskin_list', getModel('module')->getSkins($this->module_path, 'm.skins'));
			
			// SNS 서비스
			Context::set('default_services', $this->default_services);
			
			// 추가 정보 입력
			Context::set('input_add_info', array('agreement', 'user_id', 'nick_name', 'require_add_info'));
			
			$this->setTemplateFile('setting');
		}
		
		/**
		 * @brief 로그 기록
		 */
		function dispSocialxeAdminLogRecord()
		{
			// 로그 카테고리
            Context::set('category_list', array('auth_request', 'register', 'sns_clear', 'login', 'linkage', 'delete_member', 'unknown'));
			
            // 검색 옵션
			$search_option = array('nick_name', getModel('module')->getModuleConfig('member')->identifier, 'content', 'ipaddress');
            Context::set('search_option', $search_option);
			
			$args = new stdClass;
			
            if(($search_target = trim(Context::get('search_target'))) && in_array($search_target, $search_option))
			{
				$args->$search_target = str_replace(' ', '%', trim(Context::get('search_keyword')));
            }
			
		    $args->page = Context::get('page');
			$args->category = Context::get('search_category');
			
            $output = executeQuery('socialxe.getLogRecordList', $args);
			
            Context::set('total_count', $output->page_navigation->total_count);
            Context::set('total_page', $output->page_navigation->total_page);
            Context::set('page', $output->page);
            Context::set('log_record_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
			
			$this->setTemplateFile('log_record');
		}
		
		/**
		 * @brief SNS 목록
		 */
		function dispSocialxeAdminSnsList()
		{
			Context::set('sns_services', $this->config->sns_services);
			
            // 검색 옵션
			$search_option = array('nick_name', getModel('module')->getModuleConfig('member')->identifier);
            Context::set('search_option', $search_option);
			
			$args = new stdClass;
			
            if(($search_target = trim(Context::get('search_target'))) && in_array($search_target, $search_option))
			{
				$args->$search_target = str_replace(' ', '%', trim(Context::get('search_keyword')));
            }
			
		    $args->page = Context::get('page');
            $output = executeQuery('socialxe.getMemberSnsList', $args);
			
			if($output->data)
			{
				$oSocialxeModel = getModel('socialxe');
				
				foreach($output->data as $key => $val)
				{
					$val->service = array();
					
					foreach($this->config->sns_services as $key2 => $val2)
					{
						if(($sns_info = $oSocialxeModel->getMemberSns($val2, $val->member_srl)) && $sns_info->name)
						{
							$val->service[$val2] = sprintf('<a href="%s" target="_blank">%s</a>', $sns_info->profile_url, $sns_info->name);
						}
						else
						{
							$val->service[$val2] = Context::getLang('status_sns_no_register');
						}
					}
					
					$output->data[$key] = $val;	
				}
			}
			
            Context::set('total_count', $output->page_navigation->total_count);
            Context::set('total_page', $output->page_navigation->total_page);
            Context::set('page', $output->page);
            Context::set('sns_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
			
			$this->setTemplateFile('sns_list');
		}
	}