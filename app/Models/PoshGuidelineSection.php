<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoshGuidelineSection extends Model
{
    protected $table = 'posh_guideline_sections';

    protected $fillable = [
        'heading',
        'body',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function seedDefaultsIfEmpty(): void
    {
        if (static::query()->exists()) {
            return;
        }

        $sections = [
            ['1. Policy Statement', "At Expetize Private Limited, operating under the brand 1Solutions.biz, we are committed to providing a safe, secure, and respectful work environment free from sexual harassment. We have zero tolerance for any form of sexual harassment and affirm our obligation to ensure compliance with the Sexual Harassment of Women at Workplace (Prevention, Prohibition and Redressal) Act, 2013."],
            ['2. Objective', "• Prevent sexual harassment at the workplace.\n• Provide a mechanism for the redressal of complaints.\n• Promote a safe and inclusive working environment."],
            ['3. Scope', "This policy is applicable to all employees of Expetize Private Limited, including but not limited to:\n• Full-time, part-time, temporary, and contractual staff\n• Interns and trainees\n• Consultants and vendors\n• Remote or off-site employees\n\nIt covers any location considered a workplace, including company offices, client locations, work-related travel, virtual meetings, and company-sponsored events."],
            ['4. Definition of Sexual Harassment', "As per the Act, sexual harassment includes any unwelcome act or behavior (whether directly or by implication), such as:\n• Physical contact and advances\n• A demand or request for sexual favors\n• Making sexually colored remarks\n• Showing pornography\n• Any other unwelcome physical, verbal, or non-verbal conduct of a sexual nature"],
            ['5. Internal Complaints Committee (ICC)', "The company has constituted an Internal Complaints Committee (ICC) comprising:\n• Presiding Officer: A senior woman employee\n• Members: Two employees committed to the cause of women\n• External Member: A third-party NGO or expert familiar with issues related to sexual harassment\n\nThe names and contact details of ICC members will be displayed on internal communication channels."],
            ['6. Complaint Procedure', "• A complaint should be made in writing within 3 months of the incident.\n• The complaint can be submitted to the ICC via email or in person.\n• Confidentiality of the complainant and the proceedings will be maintained at all stages."],
            ['7. Redressal Process', "The ICC will conduct a fair and unbiased inquiry within 90 days. Both parties will be given a chance to be heard. If sexual harassment is proven, appropriate disciplinary action will be taken, which may include:\n• Written apology\n• Warning\n• Suspension\n• Termination of employment"],
            ['8. False Complaints', 'While the company encourages employees to speak up against misconduct, malicious or false complaints will be taken seriously and may attract disciplinary action.'],
            ['9. Awareness & Training', "• Conduct regular training and awareness programs for employees.\n• Display this policy at conspicuous places in the office.\n• Sensitize new hires during onboarding."],
            ['10. Confidentiality', 'All complaints, identities of parties involved, and proceedings will be kept confidential and disclosed only to the extent necessary for investigation and resolution.'],
            ['11. Policy Review', 'This policy will be reviewed annually or as required to ensure compliance with applicable laws and evolving best practices.'],
            ['Contact Information', "For any complaints or further information, employees are encouraged to contact the ICC.\nEmail: shashanksinghc8173@gmail.com"],
        ];

        foreach ($sections as $i => [$heading, $body]) {
            static::create([
                'heading' => $heading,
                'body' => $body,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }
}
