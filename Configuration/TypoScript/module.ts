module.tx_naworkuri {
	persistence {
		storagePid = 0
		classes {
			Nawork\NaworkUri\Domain\Model\Url {
				mapping {
					tableName = tx_naworkuri_uri
					columns {
						sys_language_uid.mapOnProperty = language
						params.mapOnProperty = parameters
					}
				}
			}
			Nawork\NaworkUri\Domain\Model\Domain {
				mapping {
					tableName = sys_domain
				}
			}
			Nawork\NaworkUri\Domain\Model\Language {
				mapping {
					tableName = sys_language
				}
			}
		}
	}
}