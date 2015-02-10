<?php
class ActionsSendProductDoc
{
	function getFormMail($parameters, &$object, &$action, $hookmanager) 
	{
		global $langs;
		$langs->load('sendproductdoc@sendproductdoc');
		
		if (in_array('formmail',explode(':',$parameters['context'])) &&
			(in_array('propalcard',explode(':',$parameters['context']))
			|| in_array('ordercard',explode(':',$parameters['context']))
			|| in_array('invoicecard',explode(':',$parameters['context']))
			|| in_array('ordersuppliercard',explode(':',$parameters['context']))
			|| in_array('invoicesuppliercard',explode(':',$parameters['context']))
		))
		{
			// Display button allowing to add product documentation as e-mail attachment
			$buttonAdd = '<input id="addproductdoc" class="button" type="submit" value="'.$langs->trans('AddProductDocAsAttachment').'" name="addproductdoc" />';
			$buttonRemove = '<input id="removeproductdoc" class="button" type="submit" value="'.$langs->trans('RemoveProductDocAsAttachment').'" name="removeproductdoc" />';
			$buttonAddObjDoc = '<input id="addobjectdoc" class="button" type="submit" value="'.$langs->trans('AddObjectDocAsAttachment').'" name="addobjectdoc" />';
			$buttonRemoveObjDoc = '<input id="removeobjectdoc" class="button" type="submit" value="'.$langs->trans('RemoveObjectDocAsAttachment').'" name="removeobjectdoc" />';
			$buttons = '<div style="text-align: center; padding: 10px;">'.$buttonAdd.$buttonRemove.$buttonAddObjDoc.$buttonRemoveObjDoc.'</div>';
			?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#addfile').after('<?php echo $buttons ?>');
				});
			</script>
			<?php
		}
		return 0;
	}
	
	function doActions($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs;
		$langs->load('sendproductdoc@sendproductdoc');
		
		// First we get the attachment list from session
		if(GETPOST('addproductdoc') || GETPOST('removeproductdoc') || GETPOST('addobjectdoc') || GETPOST('removeobjectdoc') || GETPOST('removedfile')) {
			$listofpaths = (! empty($_SESSION["listofpaths"])) ? explode(';',$_SESSION["listofpaths"]) : array();
			$listofnames = (! empty($_SESSION["listofnames"])) ? explode(';',$_SESSION["listofnames"]) : array();
			$listofmimes = (! empty($_SESSION["listofmimes"])) ? explode(';',$_SESSION["listofmimes"]) : array();
			
			$stdFunc = false;
		}
		
		$this->TFileAdded=array();
		if(!empty($listofpaths)) {
				foreach($listofpaths as $file) {
					
					$md5 = md5(file_get_contents($file));
					$this->TFileAdded[] = $md5;
				}
			
		}
		
		
		// Search for attached files to each product in the document and add it as an attachement to the e-mail
		if (GETPOST('addproductdoc'))
		{
			$nbFiles = 0;
			
			
			foreach($object->lines as $line) {
				// Get files attached to the product
				$ref = dol_sanitizeFileName($line->product_ref);
				$objectType = 'product';
				$path = $conf->{$objectType}->dir_output . '/' . $ref;
				$nbFiles += $this->_addFiles($listofpaths, $listofnames, $listofmimes, $path);
			}
			
			setEventMessage($langs->trans("XFilesHasBeenAdded",$nbFiles));
		}

		// For each attached file, look if the path comes from product and delete the attachement
		if (GETPOST('removeproductdoc'))
		{
			$objectType = 'product';
			$nbFiles = $this->_removeFiles($listofpaths, $listofnames, $listofmimes, $objectType);
			setEventMessage($langs->trans("XFilesHasBeenRemoved",$nbFiles));
		}
		
		// Search for attached files to the document and add it as an attachement to the e-mail
		if (GETPOST('addobjectdoc'))
		{
				
			// Get files attached to the document
			$ref = dol_sanitizeFileName($object->ref);
			$objectType = $object->element;
			$path = $conf->{$objectType}->dir_output . '/' . $ref;
			$nbFiles = $this->_addFiles($listofpaths, $listofnames, $listofmimes, $path);
			setEventMessage($langs->trans("XFilesHasBeenAdded",$nbFiles));
		}

		// For each attached file, look if the path comes from the doc and delete the attachement, except for the auto generated PDF
		if (GETPOST('removeobjectdoc'))
		{
			$objectType = $object->element;
			$nbFiles = $this->_removeFiles($listofpaths, $listofnames, $listofmimes, $objectType);
			setEventMessage($langs->trans("XFilesHasBeenRemoved",$nbFiles));
		}
		
		// Overload standard function to avoid physically deleting files that are product doc or object attachments
		if (GETPOST('removedfile')) {
			$iFile = GETPOST('removedfile');
			$iFile--;
			
			if(strpos($listofpaths[$iFile], $conf->product->dir_output) !== false) {
				$filename = $listofnames[$iFile];
				$this->_removeFile($listofpaths, $listofnames, $listofmimes, $iFile);
				setEventMessage($langs->trans("FileHasBeenRemoved", $filename));
			} else {
				$stdFunc = true;
			}
		}

		// Last we put back the attachments into session
		if(GETPOST('addproductdoc') || GETPOST('removeproductdoc') || GETPOST('addobjectdoc') || GETPOST('removeobjectdoc') || GETPOST('removedfile')) {
			$_SESSION["listofpaths"]=join(';',$listofpaths);
			$_SESSION["listofnames"]=join(';',$listofnames);
			$_SESSION["listofmimes"]=join(';',$listofmimes);
			
			if(!$stdFunc) {
	 			$action='presend'; // Still in presend mode
				unset($_POST['removedfile']); // Avoid standard function called when attachment is removed
			}
		}
		
		return 0;
	}

	// Add files from the list as e-mail attachments
	private function _addFiles(&$listofpaths, &$listofnames, &$listofmimes, $path) {
		global $langs;
		
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileList = dol_dir_list($path,'files',0);
		$nbFiles = 0;
		
		foreach($fileList as $fileParams) {
			// Attachment in the e-mail
			$file = $fileParams['fullname'];
			$md5 = md5(file_get_contents($file));
			
			if (! in_array($file, $listofpaths) && !in_array($md5, $this->TFileAdded)) {
				$listofpaths[] = $file;
				$this->TFileAdded[] = $md5;
				
				$listofnames[] = basename($file);
				$listofmimes[] = dol_mimetype($file);
				$nbFiles++;
			}
		}
		
		return $nbFiles;
	}
	
	// Remove files that are e-mail attachments and coming from some source
	private function _removeFiles(&$listofpaths, &$listofnames, &$listofmimes, $from, $exceptFirst=true) {
		global $conf, $langs;
		
		$nbFiles = 0;
		
		foreach($listofpaths as $i => $filePath) {
			if($exceptFirst && $i == 0) continue;
			if(strpos($filePath, $conf->{$from}->dir_output) !== false) {
				$this->_removeFile($listofpaths, $listofnames, $listofmimes, $i);
				$nbFiles++;
			}
		}
		
		return $nbFiles;
	}
	
	// Remove a file from the attachment list
	private function _removeFile(&$listofpaths, &$listofnames, &$listofmimes, $iFile) {
		global $conf, $langs;
		
		unset($listofpaths[$iFile]);
		unset($listofnames[$iFile]);
		unset($listofmimes[$iFile]);
	}
}
