<?php

class ICMLLoader {
	
	public $iblocks;
	public $filename;
        public $articleProperties;
	public $application;
	public $encoding = 'utf-8';
	
	protected $fp;
        public static function AgentLoad3( $filename)
        {
            echo $filename;
            $arFilename = "'" . $filename . "'";
            return  "ICMLLoader::AgentLoad( " . $arFilename . ");";
        
        }
        public static function AgentLoad($iblocks, $filename)
        {
            
            if (!CModule::IncludeModule("iblock")) {
                //handle err
                self::eventLog('ICMLLoader::AgentLoad', 'iblock', 'module not found');
                return true;
            }
            
            if (!CModule::IncludeModule("catalog")) {
                //handle err
                self::eventLog('ICMLLoader::AgentLoad', 'catalog', 'module not found');
                return true;
            }

            global $APPLICATION, $USER;
            if(!isset($USER)) {
               $USER = new CUser;
            }
            $loader = new ICMLLoader();
            $loader->iblocks = json_decode($iblocks, true);
            $loader->filename = $filename;
            $loader->application = $APPLICATION;
            $loader->Load();
            $arIblocks = "'" . $iblocks . "'";
            $arFilename = "'" . $filename . "'";
            return  "ICMLLoader::AgentLoad(" . $arIblocks . ", " . $arFilename . ");";
        }
	public function Load()
	{
		$categories = $this->GetCategories();
		
		$offers = $this->GetOffers();
		
		/*foreach ($offers as $obj)
			if (is_array($obj))
				foreach ($obj as $obj2)
					print(htmlspecialcharsbx($obj2) . "<br>");
			else
				print(htmlspecialcharsbx($obj) . "<br>");
			*/	
				
		
		$this->PrepareFile();
		
		$this->PreWriteCatalog();
		
		$this->WriteCategories($categories);
		$this->WriteOffers($offers);
		
		$this->PostWriteCatalog();
		
		$this->CloseFile();
		
	}
	
	protected function PrepareValue($text)
        {
		
                //$text = htmlspecialcharsbx($text);
                //$text = str_replace('&quot;', '"', $text);        
                //$text = preg_replace("/[\x1-\x8\xB-\xC\xE-\x1F]/", "", $text);
                //$text = str_replace("'", "&apos;", $text);
                $text = $this->application->ConvertCharset($text, LANG_CHARSET, $this->encoding);
                return $text;
        }
	
	protected function PrepareFile()
	{
		$fullFilename = $_SERVER["DOCUMENT_ROOT"] . $this->filename;
		CheckDirPath($fullFilename);
		
		if (!$this->fp = @fopen($fullFilename, "w"))
			return false;
		else
			return true;
	}
	
	protected function PreWriteCatalog()
	{
		@fwrite($this->fp, "<yml_catalog date=\"".Date("Y-m-d H:i:s")."\">\n");
		@fwrite($this->fp, "<shop>\n");
	
		@fwrite($this->fp, "<name>".$this->application->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, $encoding)."</name>\n");
	
		@fwrite($this->fp, "<company>".$this->application->ConvertCharset(htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "")), LANG_CHARSET, $encoding)."</company>\n");
		
	}
	
	protected function WriteCategories($categories)
	{
		@fwrite($this->fp, "<categories>\n");
		foreach ($categories as $category) {
			@fwrite($this->fp, $category . "\n");
		}
		@fwrite($this->fp, "</categories>\n");
	}
	protected function WriteOffers($offers)
	{
		@fwrite($this->fp, "<offers>\n");
		foreach ($offers as $offer) {
			@fwrite($this->fp, $offer . "\n");
		}
		@fwrite($this->fp, "</offers>\n");
	}
	
	protected function PostWriteCatalog()
	{
		@fwrite($this->fp, "</shop>\n");
		@fwrite($this->fp, "</yml_catalog>\n");
	}
	
	protected function CloseFile()
	{
		@fclose($this->fp);
	}
	
	
	protected function GetCategories()
	{
		$categories = array();
		foreach ($this->iblocks as $id) 
		{
			$filter = Array(
					"IBLOCK_ID" => $id,
					"ACTIVE" => "Y",
					"IBLOCK_ACTIVE" => "Y",
					"GLOBAL_ACTIVE" => "Y"
					);
			
			
			$dbRes = CIBlockSection::GetList(array("left_margin" => "asc"), $filter);
			while ($arRes = $dbRes->Fetch())
			{
				$categories[] = $this->BuildCategory($arRes);
			}
		}
		return $categories;

	}
	
	protected function BuildCategory($arCategory)
	{
		return "
			<category id=\"" . $arCategory["ID"] . "\""
			. ( intval($arCategory["IBLOCK_SECTION_ID"] ) > 0 ?
				" parentId=\"" . $arCategory["IBLOCK_SECTION_ID"] . "\""
				:"")
			. ">"
			. $arCategory["NAME"]
			. "</category>";
			
	}
	
	protected function GetOffers()
	{
		$offers = Array();
		foreach ($this->iblocks as $key => $id) 
		{
			
			$iblock['IBLOCK_DB'] = CIBlock::GetByID($id)->Fetch();
			$iblockOffer = CCatalogSKU::GetInfoByProductIBlock($id);
			
			
			$arSelect = Array (
					"ID",
					"LID",
					"IBLOCK_ID",
					"IBLOCK_SECTION_ID",
					"ACTIVE",
					"ACTIVE_FROM",
					"ACTIVE_TO",
					"NAME",
					"DETAIL_PICTURE",
					"DETAIL_TEXT",
					"DETAIL_PICTURE",
					"LANG_DIR",
					"DETAIL_PAGE_URL",
					"PROPERTY_" . $this->articleProperties[$key]
				);

                        $filter = Array (
					"IBLOCK_ID" => $id,
					"ACTIVE_DATE" => "Y",
					"ACTIVE" => "Y",
					"INCLUDE_SUBSECTIONS" => "Y"
				);
			$counter = 0;
                        $dbResProducts = CIBlockElement::GetList(array(), $filter, false, false, $arSelect);
			while ($product = $dbResProducts->GetNextElement()) {
				
				$product = $product->GetFields();
				
				$categoriesString = "";
				
                                
				$existOffer = false;
				if (!empty($iblockOffer['IBLOCK_ID'])) {
					$arFilterOffer = Array (
							'IBLOCK_ID' => $iblockOffer['IBLOCK_ID'],
							'PROPERTY_'.$iblockOffer['SKU_PROPERTY_ID'] => $product["ID"]
						);
					$arSelectOffer = Array (
							'ID',
							"NAME",
							"DETAIL_TEXT",
							"DETAIL_PAGE_URL",
							"DETAIL_PICTURE",
							"PROPERTY_" . $this->articleProperties[$key]
						);
					
					$rsOffers = CIBlockElement::GetList(array(), $arFilterOffer, false, false, $arSelectOffer);
					while ($arOffer = $rsOffers->GetNext()) {
						
						$dbResCategories = CIBlockElement::GetElementGroups($arOffer['ID'], true);
						while ($arResCategory = $dbResCategories->Fetch()) {
							$categoriesString .= "<categoryId>" . $arResCategory["ID"] . "</categoryId>\n";
						}
						$offer = CCatalogProduct::GetByID($arOffer['ID']);
						$arOffer['QUANTITY'] = $offer["QUANTITY"];
						
						$arOffer['PRODUCT_ID'] = $product["ID"];
						$arOffer['DETAIL_PAGE_URL'] = $product["DETAIL_PAGE_URL"];
						$arOffer['DETAIL_PICTURE'] = $product["DETAIL_PICTURE"];
						$arOffer['PREVIEW_PICTURE'] = $product["PREVIEW_PICTURE"];
						$arOffer['PRODUCT_NAME'] = $product["NAME"];
						$arOffer['ARTICLE'] = $arOffer["PROPERTY_" . $this->articleProperties[$key] . "_VALUE"];
						
						$dbPrice = GetCatalogProductPrice($arOffer["ID"],1);
						$arOffer['PRICE'] = $dbPrice['PRICE'];
						
						
						
						$offers[] = $this->BuildOffer($arOffer, $categoriesString, $iblock);
						$existOffer = true;
					}
				}
				if (!$existOffer) {
					$dbResCategories = CIBlockElement::GetElementGroups($product["ID"], true);
					while ($arResCategory = $dbResCategories->Fetch()) {
						$categoriesString .= "<categoryId>" . $arResCategory["ID"] . "</categoryId>\n";
					}
					
				
					$offer = CCatalogProduct::GetByID($product['ID']);
					$product['QUANTITY'] = $offer["QUANTITY"];
					
					$product['PRODUCT_ID'] = $product["ID"];
					$product['PRODUCT_NAME'] = $product["NAME"];
					$product['ARTICLE'] = $product["PROPERTY_" . $this->articleProperties[$key] . "_VALUE"];
					
					$dbPrice = GetCatalogProductPrice($product["ID"],1);
					$product['PRICE'] = $dbPrice['PRICE'];
					
					$offers[] = $this->BuildOffer($product, $categoriesString, $iblock);
				}
			}
		}
		return $offers;
	}
	
	
	protected function BuildOffer($arOffer, $categoriesString, $iblock)
	{
		$offer = "";
		$offer .= "<offer
				id=\"" . $arOffer["ID"] . "\"
				productId=\"" . $arOffer["PRODUCT_ID"] . "\"
				quantity=\"" . DoubleVal($arOffer['QUANTITY']) . "\"
			>\n";
		$offer .= "<url>http://" . $iblock['IBLOCK_DB']['SERVER_NAME'] . $arOffer['DETAIL_PAGE_URL'] . "</url>\n";

		$offer .= "<price>" . $arOffer['PRICE'] . "</price>\n";
		$offer .= $categoriesString;
		
		$detailPicture = intval($arOffer["DETAIL_PICTURE"]);
		$previewPicture = intval($arOffer["PREVIEW_PICTURE"]);

		if ($detailPicture > 0 || $previewPicture > 0)
		{
			$picture = $detailPicture;
			if ($picture <= 0) {
				$picture = $previewPicture;
			}

			if ($arFile = CFile::GetFileArray($picture))
			{
				if(substr($arFile["SRC"], 0, 1) == "/")
					$strFile = "http://" . $iblock['IBLOCK_DB']['SERVER_NAME'] . implode("/", array_map("rawurlencode", explode("/", $arFile["SRC"])));
				elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $arFile["SRC"], $match))
					$strFile = "http://" . $match[2] . '/' . implode("/", array_map("rawurlencode", explode("/", $match[3])));
				else
					$strFile = $arFile["SRC"];
				$offer .= "<picture>" . $strFile . "</picture>\n";
			}
		}
		
		$offer .= "<name>" . $this->PrepareValue($arOffer["NAME"]) . "</name>\n";
		$offer .= "<description>" . (strip_tags( html_entity_decode(str_replace("&nbsp;", ' ', $this->PrepareValue($arOffer["DETAIL_TEXT"]))))) .
		"</description>\n";
		

		$offer .= "<xmlId>" . $arOffer["EXTERNAL_ID"] . "</xmlId>\n";
		$offer .= "<productName>" . $arOffer["PRODUCT_NAME"] . "</productName>\n";
		$offer .= "<article>" . $arOffer["ARTICLE"] . "</article>\n";
		
		$offer.= "</offer>\n";
		return $offer;
	}
	
	
	
}