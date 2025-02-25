<?php

declare(strict_types=1);


namespace BepoPdfDatasheet\Storefront\Controller;


use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
use Shopware\Core\Checkout\Document\Service\PdfRenderer;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Product\ProductPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PdfDatasheetController extends StorefrontController
{
    private const INDEX_TWIG = '@Storefront/storefront/page/pdfdatasheet/index.html.twig';

    private ProductPageLoader $productPageLoader;

    private DocumentTemplateRenderer $documentTemplateRenderer;

    private PdfRenderer $pdfRenderer;

    public function __construct(
        ProductPageLoader $productPageLoader,
        DocumentTemplateRenderer $documentTemplateRenderer,
        PdfRenderer $pdfRenderer
    ) {
        $this->productPageLoader = $productPageLoader;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
        $this->pdfRenderer = $pdfRenderer;
    }

    #[Route(path: '/datasheet/{productId}', name: 'frontend.datasheet', options: ['seo' => false], methods: ['GET'])]
    public function product(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->productPageLoader->load($request, $context);

        if ($request->query->get('debug') && $request->query->get('debug') === 'true') {
            return $this->renderStorefront(self::INDEX_TWIG, ['page' => $page,]);
        }

        $html = $this->documentTemplateRenderer->render(self::INDEX_TWIG, ['page' => $page,], $context->getContext());
        $document = new RenderedDocument($html);

        $pdf = $this->pdfRenderer->render($document);

        $response = new StreamedResponse(
            function () use ($pdf) {
                echo $pdf;
            }
        );
        $filename = 'inline;filename="' . 'Product-Datasheet-' . $page->getProduct()->getProductNumber() . '.pdf';
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $filename);

        return $response;
    }
}
