# Table of Contents

- [Table of Contents](#table-of-contents)
- [gr-ab-testing](#gr-ab-testing)
- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Admin Panel](#admin-panel)
- [Support](#support)
- [License](#license)
  - [Simple Explanation](#simple-explanation)
  - [Detailed License Information](#detailed-license-information)
  - [Disclaimer](#disclaimer)

# gr-ab-testing
This is the name of the plugin.

# Overview
A simple AB testing plugin for WordPress. It allows you to register multiple variations for a post and measure which test post gets the most clicks.

# Features
You can easily perform AB tests.

# Installation
Download and upload the plugin's zip file to WordPress.

# Usage
- First, find the **post ID** of the post you want to test.
- Create multiple test posts using the custom post type **"GR AB Testing Variations"**. You can easily create them by copying the HTML source. It is recommended to include the test post's ID in the title for easy searching. The multiple test posts will be displayed evenly.
- Create a new custom field named **"grab_rel_post"** in the test page's custom fields and set the value to the ID of the post you are testing. This links the test post with the original post, and the test post content will be displayed instead of the original post.
- In the test post, set the link's class to **"grab-link"** and add a custom data attribute **data-grab-link="unique string"** to distinguish the links for statistics. If you cannot change the link, wrap it in a <div> or <span> and set the class to "grab-link" and the data-grab-link attribute.
- Example: &lt;div **class="grab-link"** **data-grab-link="TestLink_01"** &gt;&lt;a href="https://goro-bizaid.com"&gt;goro bizaid&lt;/a&gt;&lt;/div&gt;
- When deleting the plugin, all data will be erased, so please back up if necessary.

# Admin Panel
- The admin panel is named **"GR AB Testing"**.
- AB test statistics are displayed in real-time.
- Pressing the **"Clear Data"** button will delete all statistical information.
- Pressing the **"Download Data"** button will download all data acquired by GR AB Testing in CSV format. Use this if you need detailed data.

# Support
- For inquiries, please email **goro@goro-bizaid.com**.
- A support page for the gr-ab-testing plugin will be created at **https://goro-bizaid.com**. Please check it out.

# License
This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

## Simple Explanation
- **Free Use**: This plugin can be used freely by anyone, including for personal and commercial use.
- **Modification and Distribution**: This plugin can be modified and redistributed, but the original license must be maintained.
- **Source Code Disclosure**: If distributing a modified version, the source code must also be disclosed.
- **Patent License**: GPL 3.0 includes patent license terms, covering patent infringement considerations as well.

## Detailed License Information
For detailed license information, refer to the full text of the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

## Disclaimer
This plugin is provided "as is" without any warranty of suitability for a particular purpose or commercial usability. All risks associated with its use are borne by the user.