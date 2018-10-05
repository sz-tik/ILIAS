<?php namespace ILIAS\GlobalScreen\MainMenu\Entry;

use ILIAS\GlobalScreen\MainMenu\AbstractChildEntry;

/**
 * Class Link
 *
 * Attention: This is not the same as the \ILIAS\UI\Component\Link\Link. Please
 * read the difference between GlobalScreen and UI in the README.md of the GlobalScreen Service.
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class Link extends AbstractChildEntry {

	/**
	 * @var bool
	 */
	protected $is_external_action;
	/**
	 * @var string
	 */
	protected $action;
	/**
	 * @var string
	 */
	protected $alt_text;
	/**
	 * @var string
	 */
	protected $title;


	/**
	 * @param string $title
	 *
	 * @return Link
	 */
	public function withTitle(string $title): Link {
		$clone = clone($this);
		$clone->title = $title;

		return $clone;
	}


	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}


	/**
	 * @param string $alt_text
	 *
	 * @return Link
	 */
	public function withAltText(string $alt_text): Link {
		$clone = clone($this);
		$clone->alt_text = $alt_text;

		return $clone;
	}


	/**
	 * @return string
	 */
	public function getAltText(): string {
		return $this->alt_text;
	}


	/**
	 * @param string $action
	 *
	 * @return Link
	 */
	public function withAction(string $action): Link {
		$clone = clone($this);
		$clone->action = $action;

		return $clone;
	}


	/**
	 * @return string
	 */
	public function getAction(): string {
		return $this->action;
	}


	/**
	 * @param bool $is_external
	 *
	 * @return Link
	 */
	public function withIsLinkToExternalAction(bool $is_external): Link {
		$clone = clone $this;
		$clone->is_external_action = $is_external;

		return $clone;
	}


	/**
	 * @return bool
	 */
	public function isLinkWithExternalAction(): bool {
		return $this->is_external_action;
	}
}
