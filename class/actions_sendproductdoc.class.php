<?php
class ActionsSendProductDoc
{
	function getFormMail($parameters, &$object, &$action, $hookmanager) 
	{
		global $langs;
		$langs->load('sendproductdoc@sendproductdoc');
		
		if (in_array('formmail',explode(':',$parameters['context'])))
		{
			// Display button allowing to add product documentation as e-mail attachment
			$buttonAdd = '<input id="addproductdoc" class="button" type="submit" value="'.$langs->trans('AddProductDocAsAttachment').'" name="addproductdoc" />';
			$buttonRemove = '<input id="removeproductdoc" class="button" type="submit" value="'.$langs->trans('RemoveProductDocAsAttachment').'" name="removeproductdoc" />';
			?>
			<script type="text/javascript">
				$(document).ready(function() {
					$('#addfile').after('<br /><?= $buttonAdd.$buttonRemove ?>');
				});
			</script>
			<?
		}
		return 0;
	}
	
	function doActions($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs;
		$langs->load('sendproductdoc@sendproductdoc');
		
		// Search for attached files to each product in the document and add it as an attachement to the e-mail
		if (GETPOST('addproductdoc'))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$listofpaths = (! empty($_SESSION["listofpaths"])) ? explode(';',$_SESSION["listofpaths"]) : array();
			$listofnames = (! empty($_SESSION["listofnames"])) ? explode(';',$_SESSION["listofnames"]) : array();
			$listofmimes = (! empty($_SESSION["listofmimes"])) ? explode(';',$_SESSION["listofmimes"]) : array();
			$nbFilesAdded = 0;
			
			foreach($object->lines as $line) {
				$ref = dol_sanitizeFileName($line->ref);
				
				// Get files attached to the product
				$fileList = dol_dir_list($conf->product->dir_output . '/' . $ref,'files',0);

				foreach($fileList as $fileParams) {
					// Attachment in the e-mail
					$file = $fileParams['fullname'];
					if (! in_array($file,$listofpaths)) {
						$listofpaths[] = $file;
						$listofnames[] = basename($file);
						$listofmimes[] = dol_mimetype($file);
						$nbFilesAdded++;
					}
				}
			}
			
			$_SESSION["listofpaths"]=join(';',$listofpaths);
			$_SESSION["listofnames"]=join(';',$listofnames);
			$_SESSION["listofmimes"]=join(';',$listofmimes);
			
			setEventMessage($langs->trans("XFilesHasBeenAdded",$nbFilesAdded));
			
			$action = 'presend';
		}

		// For each attached file, look if the path comes from product and delete the attachement
		if (GETPOST('removeproductdoc'))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$listofpaths = (! empty($_SESSION["listofpaths"])) ? explode(';',$_SESSION["listofpaths"]) : array();
			$listofnames = (! empty($_SESSION["listofnames"])) ? explode(';',$_SESSION["listofnames"]) : array();
			$listofmimes = (! empty($_SESSION["listofmimes"])) ? explode(';',$_SESSION["listofmimes"]) : array();
			$nbFilesRemoved = 0;
			
			foreach($listofpaths as $i => $filePath) {
				if(strpos($filePath, $conf->product->dir_output) !== false) {
					unset($listofpaths[$i]);
					unset($listofnames[$i]);
					unset($listofmimes[$i]);
					$nbFilesRemoved++;
				}
			}
			$_SESSION["listofpaths"]=join(';',$listofpaths);
			$_SESSION["listofnames"]=join(';',$listofnames);
			$_SESSION["listofmimes"]=join(';',$listofmimes);
			
			setEventMessage($langs->trans("XFilesHasBeenRemoved",$nbFilesRemoved));
			
			$action = 'presend';
		}
		
		return 0;
	}

	function deleteFile($parameters, &$object, &$action, $hookmanager) {
		global $conf;
		
		// When user removes an attached file to the e-mail, file is deleted excepted for first one (auto generated PDF)
		// If the file came from a product, we must not delete it, just remove from e-mail attachments
		if (in_array('fileslib',explode(':',$parameters['context'])) && !empty($parameters['file'])) {
			if(strpos($parameters['file'], $conf->product->dir_output) !== false) return 'deleted';
		}
		
		return 0;
	}
}