<?php
	namespace src\components\Directives;
	class Dropdown extends \Hyphe\Engine {
	
	public function render()
	{
	
		$assets = $this->loadAssets();
	?>
	
			<div class="dropdown-modal-wrapper">
				<div class="dropdown-modal-box" data-dropdown-modal-child="yes">
					<div class="dropdown-container">
						<?php $other = fdb()->get('partials.others');?>
						<?=\Happy\Directives::runDirective(true,'partial',$other->value($this->props->partial))?>
					</div>
				</div>
				<div class="dropdown-modal-button-wrapper dropdown-container" data-dropdown-modal-child="yes">
					<span class="dropdown-modal-button dropdown-modal-button-cancel">
						<img src="<?=\Happy\Directives::runDirective(true,'image','close-black-thin.svg')?>">
					</span>
					<span class="dropdown-modal-button dropdown-modal-button-accept">
						<img src="<?=\Happy\Directives::runDirective(true,'image','check-white-thin.svg')?>">
					</span>
				</div>
			</div>
		
	<?php
	
	}

	public static function ___cacheData()
	{
	  return "5dc411a402ae078b52e58f28a7c45000";
	}
	}