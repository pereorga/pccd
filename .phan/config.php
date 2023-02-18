<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

declare(strict_types=1);

/*
 * This configuration will be read and overlaid on top of the
 * default configuration. Command line arguments will be applied
 * after this file is read.
 */

return [
    // Supported values: `'5.6'`, `'7.0'`, `'7.1'`, `'7.2'`, `'7.3'`, `'7.4'`,
    // `'8.0'`, `'8.1'`, `null`.
    // If this is set to `null`,
    // then Phan assumes the PHP version which is closest to the minor version
    // of the php executable used to execute Phan.
    'target_php_version' => null,

    'error_prone_truthy_condition_detection' => true,
    'force_tracking_references' => true,
    'redundant_condition_detection' => true,
    // 'strict_method_checking' => true,
    // 'strict_param_checking' => true,
    'strict_property_checking' => true,
    'strict_return_checking' => true,
    'unused_variable_detection' => true,

    // A list of directories that should be parsed for class and
    // method information. After excluding the directories
    // defined in exclude_analysis_directory_list, the remaining
    // files will be statically analyzed for errors.
    //
    // Thus, both first-party and third-party code being used by
    // your application should be included in this list.
    'directory_list' => [
        'docroot',
        'scripts',
        'src',
    ],

    // A directory list that defines files that will be excluded
    // from static analysis, but whose class and method
    // information should be included.
    //
    // Generally, you'll want to include the directories for
    // third-party code (such as "vendor/") in this list.
    //
    // n.b.: If you'd like to parse but not analyze 3rd
    //       party code, directories containing that code
    //       should be added to the `directory_list` as
    //       to `exclude_analysis_directory_list`.
    'exclude_analysis_directory_list' => [
        'src/db_settings.php',
        'src/tideways_xhprof_append.php',
        'src/tideways_xhprof_prepend.php',
        'src/third_party/',
        'src/xhprof.php',
        'vendor/',
    ],

    // A list of plugin files to execute.
    // Plugins which are bundled with Phan can be added here by providing their name
    // (e.g. 'AlwaysReturnPlugin')
    //
    // Documentation about available bundled plugins can be found
    // at https://github.com/phan/phan/tree/v5/.phan/plugins
    //
    // Alternately, you can pass in the full path to a PHP file
    // with the plugin's implementation.
    // (e.g. 'vendor/phan/phan/.phan/plugins/AlwaysReturnPlugin.php')
    'plugins' => [
        'AddNeverReturnTypePlugin',
        'AlwaysReturnPlugin',
        'AvoidableGetterPlugin',
        'ConstantVariablePlugin',
        'DeprecateAliasPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateConstantPlugin',
        'DuplicateExpressionPlugin',
        'EmptyMethodAndFunctionPlugin',
        'EmptyStatementListPlugin',
        // 'HasPHPDocPlugin',
        // 'InlineHTMLPlugin',
        'InvalidVariableIssetPlugin',
        'InvokePHPNativeSyntaxCheckPlugin',
        'LoopVariableReusePlugin',
        // 'MoreSpecificElementTypePlugin',
        'NoAssertPlugin',
        'NonBoolBranchPlugin',
        'NotFullyQualifiedUsagePlugin',
        // 'NumericalComparisonPlugin',
        'PHPDocInWrongCommentPlugin',
        'PHPDocRedundantPlugin',
        'PHPDocToRealTypesPlugin',
        // 'PHPUnitAssertionPlugin',
        'PossiblyStaticMethodPlugin',
        'PreferNamespaceUsePlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'RedundantAssignmentPlugin',
        // 'RemoveDebugStatementPlugin',
        'ShortArrayPlugin',
        'SimplifyExpressionPlugin',
        'SleepCheckerPlugin',
        'StaticVariableMisusePlugin',
        'StrictComparisonPlugin',
        'StrictLiteralComparisonPlugin',
        'SuspiciousParamOrderPlugin',
        // 'UnknownClassElementAccessPlugin',
        'UnknownElementTypePlugin',
        'UnreachableCodePlugin',
        'UnsafeCodePlugin',
        'UnusedSuppressionPlugin',
        'UseReturnValuePlugin',
        'WhitespacePlugin',
    ],
];
