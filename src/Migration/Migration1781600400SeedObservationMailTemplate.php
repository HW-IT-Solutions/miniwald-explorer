<?php declare(strict_types=1);

namespace InReNa\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Seeds the mail template type and a default mail template for the
 * "miniwald.observation.submitted" Flow event.
 *
 * After running this migration the admin can:
 *  1. Go to Settings → Flow Builder → Create Flow
 *  2. Set trigger: "miniwald.observation.submitted"
 *  3. Add action: "Send email" → select template "Neue Beobachtung eingereicht"
 *
 * The recipient is configured in the Flow action itself (or pre-filled from
 * the plugin system config MiniwaldExplorer.config.adminNotificationEmail).
 */
class Migration1781600400SeedObservationMailTemplate extends MigrationStep
{
    // Fixed UUIDs — deterministic so re-running is idempotent
    private const TEMPLATE_TYPE_ID = '0f8a3b2c1d4e5f6a7b8c9d0e1f2a3b4c';
    private const TEMPLATE_ID      = '1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d';

    // Language IDs (standard Shopware defaults)
    private const LANG_DE = '2fbb5fe2e29a4d70aa5854ce7ce3e20b';
    private const LANG_EN = '8f36a9dc3dfd4a7a9b1c2d3e4f5a6b7c';

    public function getCreationTimestamp(): int
    {
        return 1781600400;
    }

    public function update(Connection $connection): void
    {
        // 1. Mail template type
        $connection->executeStatement(
            'INSERT IGNORE INTO `mail_template_type`
                (`id`, `technical_name`, `available_entities`, `template_data`, `created_at`)
             VALUES
                (UNHEX(:id), :technical_name, :available_entities, :template_data, NOW())',
            [
                'id'                 => self::TEMPLATE_TYPE_ID,
                'technical_name'     => 'observation_submitted',
                'available_entities' => json_encode([
                    'observationId' => 'string',
                    'customerName'  => 'string',
                    'customerEmail' => 'string',
                    'miniwaldName'  => 'string',
                    'observedAt'    => 'string',
                    'weather'       => 'string',
                    'wind'          => 'string',
                    'itemCount'     => 'int',
                ]),
                'template_data'      => json_encode([]),
            ]
        );

        // 2. Mail template type translations
        $this->insertTypeTranslation(
            $connection,
            self::TEMPLATE_TYPE_ID,
            self::LANG_DE,
            'Neue Beobachtung eingereicht',
        );
        $this->insertTypeTranslation(
            $connection,
            self::TEMPLATE_TYPE_ID,
            self::LANG_EN,
            'New observation submitted',
        );

        // 3. Mail template
        $connection->executeStatement(
            'INSERT IGNORE INTO `mail_template`
                (`id`, `mail_template_type_id`, `system_default`, `created_at`)
             VALUES
                (UNHEX(:id), UNHEX(:type_id), 0, NOW())',
            [
                'id'      => self::TEMPLATE_ID,
                'type_id' => self::TEMPLATE_TYPE_ID,
            ]
        );

        // 4. Mail template translations
        $this->insertTemplateTranslation(
            $connection,
            self::TEMPLATE_ID,
            self::LANG_DE,
            'Neue Beobachtung eingereicht – MiniwaldExplorer',
            $this->getHtmlBodyDe(),
            $this->getPlainBodyDe(),
        );
        $this->insertTemplateTranslation(
            $connection,
            self::TEMPLATE_ID,
            self::LANG_EN,
            'New observation submitted – MiniwaldExplorer',
            $this->getHtmlBodyEn(),
            $this->getPlainBodyEn(),
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // intentionally empty
    }

    private function insertTypeTranslation(
        Connection $connection,
        string $typeId,
        string $languageId,
        string $name,
    ): void {
        $connection->executeStatement(
            'INSERT IGNORE INTO `mail_template_type_translation`
                (`mail_template_type_id`, `language_id`, `name`, `created_at`)
             VALUES
                (UNHEX(:type_id), UNHEX(:lang_id), :name, NOW())',
            [
                'type_id' => $typeId,
                'lang_id' => $languageId,
                'name'    => $name,
            ]
        );
    }

    private function insertTemplateTranslation(
        Connection $connection,
        string $templateId,
        string $languageId,
        string $subject,
        string $contentHtml,
        string $contentPlain,
    ): void {
        $connection->executeStatement(
            'INSERT IGNORE INTO `mail_template_translation`
                (`mail_template_id`, `language_id`, `subject`, `content_html`, `content_plain`, `created_at`)
             VALUES
                (UNHEX(:template_id), UNHEX(:lang_id), :subject, :content_html, :content_plain, NOW())',
            [
                'template_id'   => $templateId,
                'lang_id'       => $languageId,
                'subject'       => $subject,
                'content_html'  => $contentHtml,
                'content_plain' => $contentPlain,
            ]
        );
    }

    private function getHtmlBodyDe(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #2d6a2d;">Neue Beobachtung eingereicht – MiniwaldExplorer</h2>

    <p>Eine neue Beobachtung wurde eingereicht und wartet auf Überprüfung.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold; width: 40%;">Beobachtungs-ID</td>
            <td style="padding: 8px 12px;">{{ observationId }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold;">Beobachter</td>
            <td style="padding: 8px 12px;">{{ customerName }} ({{ customerEmail }})</td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold;">Miniwald</td>
            <td style="padding: 8px 12px;">{{ miniwaldName ?: '–' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold;">Beobachtungsdatum</td>
            <td style="padding: 8px 12px;">{{ observedAt }}</td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold;">Wetter</td>
            <td style="padding: 8px 12px;">{{ weather ?: '–' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold;">Wind</td>
            <td style="padding: 8px 12px;">{{ wind ?: '–' }}</td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold;">Anzahl Arten</td>
            <td style="padding: 8px 12px;">{{ itemCount }}</td>
        </tr>
    </table>

    <p style="color: #666; font-size: 12px; margin-top: 30px;">
        Diese E-Mail wurde automatisch vom MiniwaldExplorer-System gesendet.
    </p>
</div>
HTML;
    }

    private function getPlainBodyDe(): string
    {
        return <<<'TEXT'
Neue Beobachtung eingereicht – MiniwaldExplorer
===============================================

Eine neue Beobachtung wurde eingereicht und wartet auf Überprüfung.

Beobachtungs-ID: {{ observationId }}
Beobachter:      {{ customerName }} ({{ customerEmail }})
Miniwald:        {{ miniwaldName ?: '–' }}
Datum:           {{ observedAt }}
Wetter:          {{ weather ?: '–' }}
Wind:            {{ wind ?: '–' }}
Anzahl Arten:    {{ itemCount }}

Diese E-Mail wurde automatisch vom MiniwaldExplorer-System gesendet.
TEXT;
    }

    private function getHtmlBodyEn(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2 style="color: #2d6a2d;">New Observation Submitted – MiniwaldExplorer</h2>

    <p>A new observation has been submitted and is awaiting review.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold; width: 40%;">Observation ID</td>
            <td style="padding: 8px 12px;">{{ observationId }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold;">Observer</td>
            <td style="padding: 8px 12px;">{{ customerName }} ({{ customerEmail }})</td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold;">Mini-forest</td>
            <td style="padding: 8px 12px;">{{ miniwaldName ?: '–' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold;">Observed at</td>
            <td style="padding: 8px 12px;">{{ observedAt }}</td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold;">Weather</td>
            <td style="padding: 8px 12px;">{{ weather ?: '–' }}</td>
        </tr>
        <tr>
            <td style="padding: 8px 12px; font-weight: bold;">Wind</td>
            <td style="padding: 8px 12px;">{{ wind ?: '–' }}</td>
        </tr>
        <tr style="background: #f5f5f5;">
            <td style="padding: 8px 12px; font-weight: bold;">Species count</td>
            <td style="padding: 8px 12px;">{{ itemCount }}</td>
        </tr>
    </table>

    <p style="color: #666; font-size: 12px; margin-top: 30px;">
        This email was sent automatically by the MiniwaldExplorer system.
    </p>
</div>
HTML;
    }

    private function getPlainBodyEn(): string
    {
        return <<<'TEXT'
New Observation Submitted – MiniwaldExplorer
============================================

A new observation has been submitted and is awaiting review.

Observation ID: {{ observationId }}
Observer:       {{ customerName }} ({{ customerEmail }})
Mini-forest:    {{ miniwaldName ?: '–' }}
Observed at:    {{ observedAt }}
Weather:        {{ weather ?: '–' }}
Wind:           {{ wind ?: '–' }}
Species count:  {{ itemCount }}

This email was sent automatically by the MiniwaldExplorer system.
TEXT;
    }
}
