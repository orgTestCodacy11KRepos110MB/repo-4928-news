<?php
declare(strict_types=1);

namespace OCA\News\Search;

use OCA\News\Service\FeedServiceV2;
use OCA\News\Service\ItemServiceV2;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

/**
 * Class ItemSearchProvider
 *
 * @package OCA\News\Search
 */
class ItemSearchProvider implements IProvider
{
    /** @var IL10N */
    private $l10n;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var FeedServiceV2 */
    private $service;

    public function __construct(IL10N $l10n, IURLGenerator $urlGenerator, ItemServiceV2 $service)
    {
        $this->l10n = $l10n;
        $this->urlGenerator = $urlGenerator;
        $this->service = $service;
    }

    public function getId(): string
    {
        return 'news_item';
    }

    public function getName(): string
    {
        return $this->l10n->t('News articles');
    }

    public function getOrder(string $route, array $routeParameters): int
    {
        if ($route === 'news.page.index') {
            // Active app, prefer my results
            return -1;
        }

        return 65;
    }

    private function strip_truncate(string $string, int $length=20): string {
        $string = strip_tags(trim($string));
      
        if(strlen($string) > $length) {
          $string = wordwrap($string, $length);
          $string = explode("\n", $string, 2);
          $string = $string[0];
        }
      
        return $string;
    }

    public function search(IUser $user, ISearchQuery $query): SearchResult
    {
        $list = [];
        $term = strtolower($query->getTerm());

        foreach ($this->service->findAllForUser($user->getUID()) as $item) {
            if (strpos(strtolower($item->getTitle()), $term) === false) {
                continue;
            }

            $list[] = new SearchResultEntry(
                $this->urlGenerator->imagePath('core', 'filetypes/text.svg'),
                $item->getTitle(),
                $this->strip_truncate($item->getBody(), 50),
                $this->urlGenerator->linkToRoute('news.page.index') . '#/items/feeds/' . $item->getFeedId()
            );

        }

        return SearchResult::complete($this->l10n->t('News'), $list);
    }
}
