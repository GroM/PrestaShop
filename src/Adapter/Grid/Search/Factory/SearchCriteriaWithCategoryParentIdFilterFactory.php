<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Adapter\Grid\Search\Factory;

use Category;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShop\PrestaShop\Core\Feature\FeatureInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\Factory\DecoratedSearchCriteriaFactory;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteria;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use PrestaShop\PrestaShop\Core\Multistore\MultistoreContextCheckerInterface;
use Tools;

/**
 * Class SearchCriteriaWithCategoryParentIdFilterFactory.
 *
 * @internal
 */
final class SearchCriteriaWithCategoryParentIdFilterFactory implements DecoratedSearchCriteriaFactory
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Context
     */
    private $shopContext;

    /**
     * @var FeatureInterface
     */
    private $multistoreFeature;

    /**
     * @var MultistoreContextCheckerInterface
     */
    private $multistoreContextChecker;

    /**
     * @var int
     */
    private $contextShopCategoryId;

    /**
     * @param Configuration $configuration
     * @param Context $shopContext
     * @param FeatureInterface $multistoreFeature
     * @param MultistoreContextCheckerInterface $multistoreContextChecker
     * @param int $contextShopCategoryId
     */
    public function __construct(
        Configuration $configuration,
        Context $shopContext,
        FeatureInterface $multistoreFeature,
        MultistoreContextCheckerInterface $multistoreContextChecker,
        $contextShopCategoryId
    ) {
        $this->configuration = $configuration;
        $this->shopContext = $shopContext;
        $this->multistoreFeature = $multistoreFeature;
        $this->multistoreContextChecker = $multistoreContextChecker;
        $this->contextShopCategoryId = $contextShopCategoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createFrom(SearchCriteriaInterface $searchCriteria)
    {
        $categoryParentId = $this->resolveCategoryParentId();

        $filters = array_merge(
            ['id_category_parent' => $categoryParentId],
            $searchCriteria->getFilters()
        );

        return new SearchCriteria(
            $filters,
            $searchCriteria->getOrderBy(),
            $searchCriteria->getOrderWay(),
            $searchCriteria->getOffset(),
            $searchCriteria->getLimit()
        );
    }

    /**
     * @return int Category parent id
     */
    private function resolveCategoryParentId()
    {
        if (Tools::isSubmit('id_category')) {
            return (int) Tools::getValue('id_category');
        }

        $categoriesCountWithoutParent = count(Category::getCategoriesWithoutParent());
        $isMultistoreFeatureUsed = $this->multistoreFeature->isUsed();

        if (!$isMultistoreFeatureUsed && $categoriesCountWithoutParent > 1) {
            return $this->configuration->getInt('PS_ROOT_CATEGORY');
        }

        if ($isMultistoreFeatureUsed && 1 === $categoriesCountWithoutParent) {
            return $this->configuration->getInt('PS_HOME_CATEGORY');
        }

        if ($isMultistoreFeatureUsed
            && $categoriesCountWithoutParent > 1
            && !$this->multistoreContextChecker->isSingleShopContext()
        ) {
            if ($this->multistoreFeature->isActive()
                && count($this->shopContext->getShops(true, true)) === 1
            ) {
                return $this->contextShopCategoryId;
            }

            return $this->configuration->getInt('PS_ROOT_CATEGORY');
        }

        return $this->contextShopCategoryId;
    }
}
